# Implementation Plan: "Golden Plate" Validation Benchmark (PRD B)

Pairs with `docs/prd-golden-plate-validation-benchmark.md`, which holds the *what* and *why*. This document is the build sequence — the *how* and the *when*.

## The core reframe: we are not blocked

The weighed-meal dataset is the *input* you feed the instrument, not a prerequisite for building it. The entire measuring apparatus — metric math, harness, report — can be built and fully tested now, with zero meals and zero provider spend (using the agent fake). The *first* thing we ship, the collection protocol, is precisely what unblocks you to start weighing meals.

Only one thing genuinely waits on the dataset: pressing "run" for real and publishing first-party numbers.

## What the harness runs — the production path, verbatim

Two entry points, benchmarked side by side:

- `App\Ai\Agents\FoodPhotoAnalyzerAgent::analyze()` — the raw model estimate.
- `App\Actions\AnalyzeFoodPhotoAction::handle()` — model **plus** USDA reference enrichment (now enabled by default).

Running both answers a live question: whether the USDA hybrid — shipped *without* this gate — helps or quietly hurts. Every result is stamped with `FoodPhotoAnalyzerAgent::version()` (e.g. `gemini-3.5-flash/p3`) so findings attribute to an exact analyzer version, and regressions show up as deltas between versions.

A guiding rule from the PRD: **accuracy numbers are findings, never test assertions.** Tests verify the instrument; they never pin a measured value.

## Phases

### Phase 0 — Collection protocol + dataset substrate  · unblocks you · ✅ shipped 2026-06-12, revised same day to DB + S3 (founder direction)

The long-lead, physical-world item. Shipped first so meal collection can start in a format the harness already understands.

- The protocol document (`docs/golden-plate-collection-protocol.md`): how to photograph (single phone, varied angle/lighting/distance, deliberately include large portions to measure the underestimation-vs-portion slope), how to weigh (kitchen scale, per-item where feasible), which reference computes "truth" (USDA FoodData Central / product labels), how to decompose mixed dishes, and when to record whole-meal-only truth.
- The dataset substrate the harness reads: `benchmark_meals` + `benchmark_meal_items` tables (per-100g truth + weight per item; as-served values derived), photos on the benchmark photo disk (`plate.benchmark.photo_disk` — S3 in Cloud environments, `BENCHMARK_PHOTO_DISK` override locally), with the disk + path stored per row.
- Entry tooling: `php artisan benchmark:add-meal <photo>` — interactive prompts (selects for all enums), validation at entry (weight-sum ±5% guard, Atwater ±25% warning), photo upload named by sequential meal code, one transaction. This replaced the original CSV-manifest design: an earlier iteration used hand-filled CSVs + a local photos folder, superseded because Cloud filesystems are ephemeral, dev/prod already run S3, and DB entry validates at collection time instead of in a later validator pass.
- Two tranches with different trust levels, per the PRD: headline numbers always come from the hand-collected tranche; any public-dataset tranche is reported separately with a training-contamination caveat.

Deliverable: collection can start immediately, and the data already fits Phase 2.

### Phase 1 — Metric module  · pure math · unit-tested · ✅ shipped 2026-06-12 (`app/Services/Benchmark/MetricsCalculator` + `app/Data/Benchmark/` DTOs)

A deep, pure module (`app/Services/Benchmark/`) operating on `(predicted, truth)` pairs. No I/O, no provider calls — fully testable in isolation. Input: a list of `MealEvaluation` (meal code, weight, truth `NutrientValues`, n `PredictedRun`s); output: a `BenchmarkMetrics` DTO. Semantics: macro-averaged — per-run errors are averaged within each meal first, then across meals, so every meal counts equally regardless of repeat count; zero-truth meals stay in MAE but are excluded from MAPE and ratio error; per-bin calibration errors use medians (robust at small n), with the top bin inclusive of 100.

- Carbohydrate MAE (grams) and MAPE — **primary**.
- Energy, protein, fat MAPE.
- Macro-ratio error: percentage-point deviation of carb/protein/fat energy shares (measured separately from absolute-gram error — robustness of ratios is its own question).
- Portion bias: slope of signed error against meal size (linear fit).
- Run-to-run standard deviation per nutrient.
- Confidence calibration: bin results by reported confidence → per-bin error + a reliability curve, surfaced as an expected-error-at-confidence table.
- Unit tests on synthetic fixtures: known inputs → known outputs. This arithmetic is load-bearing for every published claim.
- Per-item identification recall/precision (a PRD metric) deliberately rides with Phase 2 instead — it needs predicted item lists and a name-matching policy, which belong to the harness boundary.

### Phase 2 — Harness + console command  · agent-fake tested · ✅ shipped 2026-06-12 (`app/Services/Benchmark/BenchmarkHarness` + `ItemMatcher` + `benchmark:run`)

- A runner that, per golden plate, loads the meal row + its photo from the benchmark disk, invokes the production path verbatim (both entry points above), performs n=5 repeats, and records the analyzer version with every result.
- `benchmark:run` command following existing command conventions (`#[Description]` + `#[Signature]`, progress bar): a full run for gating and a fixed `--smoke` subset for cheap iteration. It **estimates provider cost up front** from `plate.model_pricing` (meals × repeats × paths × pinned-model token budget) and only runs the full set deliberately.
- Feature test on the existing `FoodPhotoAnalyzerAgent::fake()` pattern: confirms the harness hits the real path, performs the repeats, and stamps the version — asserting behavior, never accuracy values.
- Itemization recall/precision landed here as planned: `ItemMatcher` scores predicted item names (canonical `match_name` preferred) against *visible* truth items via normalized token overlap-coefficient ≥ 0.5, greedy one-to-one assignment; per-run scores flow into `PredictedRun` and aggregate macro-averaged in the metric module. Hidden ingredients never penalize recall — their macros already live in the totals truth.
- Failure tolerance: a throwing analysis is counted (`failedRuns`) and skipped, an unreadable photo skips the meal (`skippedMeals`) — a long real run never dies mid-way. Results are console-only until Phase 3 persists them.

### Phase 3 — Versioned report artifact  · ✅ shipped 2026-06-12 (`benchmark_runs` table + `RunComparator`)

- Every completed `benchmark:run` persists one `benchmark_runs` row: analyzer version (indexed), reference-lookup flag, smoke flag, repeats, meal/skipped counts, and the full `HarnessReport` as JSON (Spatie Data round-trips it back to typed DTOs via `BenchmarkRun::toHarnessReport()`).
- Deltas: before saving, the command loads the latest *comparable* previous run (same smoke flag — full runs never compare against smoke subsets) and `RunComparator` renders signed per-path deltas (Δ carb MAE/MAPE, Δ energy MAPE, Δ item recall) — positive error delta = regression, visible at a glance. The model-pinning PRD's upgrade gate is now mechanically answerable: run, read the delta table.
- A declined cost confirmation persists nothing; the console render remains the human-readable summary, the JSON column the machine-readable artifact.

### Phase 4 — Publish  · build now · wording flips only on real data

- Feed the calibration table and headline carb error into `App\Services\AiTransparency` (the single-source content service) → the measured-accuracy section of `/ai-accuracy` and the researcher fact sheet.
- Until a real run exists, the page keeps its literature-baseline framing and the confidence score stays described as uncalibrated. That wording changes *only* when the calibration table exists. The page was already structured to receive these numbers.

### → The real run · dataset-gated

Once the hand-collected tranche lands: run the full benchmark for real (real credits) → first-party numbers → flip the page wording and answer the AUT group with our own measured figures, methodology attached.

## Recommended order

Phase **0** first (it unblocks your collection and is a document + a format decision, zero code risk), then **1 → 2 → 3** as independent, individually shippable slices — one per PR, each testable without the dataset. Phase **4** and the real run follow once meals exist.

## Decisions taken (override if you disagree)

- **DB + S3 substrate** (founder direction, superseding the original filesystem/CSV design): truth in `benchmark_meals`/`benchmark_meal_items`, photos on the configurable benchmark disk, entry via the interactive `benchmark:add-meal` command with validation at collection time. Durable in Cloud environments, queryable, factory-testable; a CSV/JSON export command can be added later for sharing the dataset with researchers.
- **Benchmark both** the raw agent and the enriched action, so the USDA-hybrid-by-default decision finally gets validated.
- **Metric math as a deep, pure module**, unit-tested on synthetic fixtures; report formatting and specific accuracy values are deliberately not asserted.

## Out of scope (owned elsewhere or deferred)

- Changing models/prompts in response to results (the model-pinning PRD owns the upgrade procedure; this only supplies its gate).
- Clinical validation claims, micronutrients, and continuous online evaluation from user corrections (a promising future weak-label source, not built now).
- The transparency page itself (PRD A).
