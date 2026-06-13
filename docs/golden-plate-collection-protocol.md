# Golden Plate Collection Protocol

Ground-truth collection protocol for the food photo analysis validation benchmark. Pairs with `prd-golden-plate-validation-benchmark.md` (the what/why) and `plan-golden-plate-validation-benchmark.md` (the build plan). This document governs how meals are photographed, weighed, and recorded so that the resulting dataset is citable as published methodology.

The prime directive: **ground truth must never be an estimate of the same kind the model makes.** Every recorded value traces to a scale reading plus a label, reference entry, or weighed recipe. If a meal's truth cannot be computed that way, the meal is not collectible — skip it.

## Where the dataset lives

Ground truth is stored in the application database (`benchmark_meals` + `benchmark_meal_items`); photos are stored on the benchmark photo disk — S3 in the Cloud environments, configurable locally via `BENCHMARK_PHOTO_DISK=s3` so photos collected on a laptop land in the shared bucket. Each meal row records the disk and path its photo was stored on, so the harness always finds the right file.

Recording a meal is one command:

```bash
php artisan benchmark:add-meal ~/Desktop/IMG_4203.jpg
```

It prompts for every field (selects for all enumerated values — no typos possible), validates at entry, uploads the photo named after the meal code (`m0001.jpg`), and writes everything in one transaction. Durability comes from managed Postgres backups plus S3 — there is nothing to commit or back up by hand.

## What counts as a collectible meal

- Real meals you would actually eat, plated normally — this benchmarks real usage, not studio conditions.
- Every component must be weighable and truth-computable (see truth sources below). Restaurant or takeaway food qualifies only when it has a trustworthy label or can be decomposed and weighed.
- Caloric drinks (juice, milky coffee, soft drinks) are part of the meal and get item rows. Water, black coffee, and zero-calorie drinks are ignored.
- The same dish may appear at most 3 times in the dataset, and only under different conditions (lighting, angle, portion).

## Photographing

1. Exactly **one photo per meal**, taken **before eating**, with a phone camera — shot the way a real user would shoot it.
2. **No deliberate scale references** in frame (no coins, cards, rulers, scales). Natural tableware only. Real users don't stage reference objects, so neither does the benchmark.
3. No faces, hands, or identifying information in frame — photos may be shared with researchers as part of the published methodology.
4. No edits, crops, or filters. JPEG or PNG only (iPhone: Settings → Camera → Formats → Most Compatible, or export as JPEG) — the command rejects anything else.
5. Vary lighting, angle, and distance across the dataset per the distribution targets below. Do not curate away realistically imperfect shots — only discard accidental garbage (covered lens, wrong subject).
6. Get the photo onto the machine running the command (AirDrop works) and pass its path as the argument — naming and storage are handled for you.

## Weighing

- Digital kitchen scale with 1 g resolution. Sanity-check it monthly against a sealed packaged product's declared net weight.
- Weigh everything **as served** (cooked state), taring between components while plating.
- **Hidden ingredients are items too.** Cooking oil, butter, dressings, and sugar stirred into drinks are weighed (or spoon-measured and converted to grams) and recorded as their own item rows answered "not visible". The model is expected to miss them — measuring that miss is part of the point.
- Weigh carb-bearing items (starches, sugars, fruit) with the most care — carbohydrate error is the benchmark's primary metric.
- The total meal weight is always recorded (tare the empty plate first, or sum the items). It is the x-axis of the portion-bias slope.

## Computing truth

Each item's truth is recorded **per 100 g** plus its weight; the system computes as-served values. This keeps manual arithmetic to near zero. Sources, in order of preference:

| Truth source | Use when | Truth reference records |
|---|---|---|
| `label` | The exact packaged product was used | Product name as labelled |
| `reference` | A USDA FoodData Central entry matches the prepared food | The FDC ID |
| `recipe` | Home-cooked mixed dish decomposed by weighed ingredients | A short recipe identifier |

- Reference values must match the **prepared state**: "rice, white, cooked" — never raw-state values for cooked food.
- If a label gives per-serving values only, convert to per-100 g once (values ÷ serving grams × 100) and note it in the meal notes.
- **Recipe method** (for stews, curries, dumplings — anything not decomposable at plating): weigh every raw ingredient while cooking, compute each ingredient's truth from label/reference, sum, then divide by the cooked total weight. The result is a per-100 g truth for the dish itself, recorded as **one item row**; the served portion is just its weight.

### Per-item vs meal-only truth

The truth scope declares how much truth a meal carries:

- **`per-item`** (preferred — target ≥70% of meals): *every* component, visible or hidden, has an item row. Meal totals are derived from the items — never entered by hand. Per-item truth is what powers itemization recall/precision metrics.
- **`meal-only`**: whole-meal truth is solid but components can't be itemized (e.g. a labelled frozen ready-meal). No item rows; the four macro totals plus a meal-level truth source/reference are entered instead.
- A meal where only *some* components are truth-computable is **not** a per-item meal. Decompose fully, fall back to meal-only if whole-meal truth exists, otherwise exclude.

## What gets recorded

Per meal: tranche (`hand`/`public`), collection date, cuisine (lowercase tag, e.g. `mongolian`, `western`), dish type (`whole` = visually separable components; `mixed` = blended/occluded — stews, curries, buuz, burgers), lighting (`bright`/`indoor`/`dim`), camera angle (`top-down`/`angled`/`side`), truth scope, total weight, and free-text notes. Meal-only meals additionally record the four macro totals and their truth source/reference.

Per item: name (plain English, prepared state — `"rice, white, cooked"`; itemization metrics match against this), visibility in the photo, as-served weight, the four per-100 g values, truth source, and truth reference.

### Worked example (illustrative values, not authoritative)

A grilled chicken plate, fully itemized — total weight 428 g, four item rows:

| # | Item | Visible | Weight (g) | kcal/100g | Carbs | Protein | Fat | Source | Ref |
|---|---|---|---|---|---|---|---|---|---|
| 1 | chicken breast, grilled | yes | 150 | 165 | 0 | 31 | 3.6 | reference | FDC 2646170 |
| 2 | rice, white, cooked | yes | 180 | 130 | 28.2 | 2.7 | 0.3 | reference | FDC 2512381 |
| 3 | broccoli, steamed | yes | 90 | 35 | 7.2 | 2.4 | 0.4 | reference | FDC 2685573 |
| 4 | olive oil | **no** | 8 | 884 | 0 | 0 | 100 | reference | FDC 1750351 |

A labelled frozen lasagna, meal-only — 400 g served, label reads 158 kcal / 17 c / 7 p / 6.8 f per 100 g, so totals are 632 kcal / 68 c / 28 p / 27.2 f, source `label`, reference "Brand X beef lasagna 400g".

## Validation at entry

The command enforces the format so errors surface while the meal is still on the counter:

1. Photo must exist and be jpg/jpeg/png; it is stored as `<code>.<ext>` automatically — codes are assigned sequentially and never reused.
2. All enumerated fields are select prompts — invalid values are impossible.
3. Weights and totals must be positive numbers; macros non-negative.
4. Per-item meals: item weights must sum to within ±5% of the total weight — a bigger gap means a weighing error or an unrecorded component, and the command asks before recording it.
5. Atwater sanity check per item and per meal-only total: kcal within ±25% of `4×protein + 4×carbs + 9×fat` (the tolerance absorbs fiber and label rounding) — a warning, not a rejection.

## Distribution targets (hand tranche, at 150+ meals)

These are soft quotas — they exist so the dataset can measure the failure modes the literature documents (portion-size bias, occlusion, cuisine coverage, image conditions):

| Dimension | Target |
|---|---|
| Portion size | ≥25% large meals (>600 g); spread across small (<300 g) / medium / large |
| Dish type | ≥30% `mixed` |
| Cuisine | ≥3 cuisines with ≥20 meals each; include at least one under-documented cuisine (e.g. `mongolian`) |
| Lighting | ≥20% non-bright, of which ≥10% `dim` |
| Angle | No single angle above 70% |
| Truth depth | ≥70% `per-item` |

Portion bands are derived from the total weight — nothing extra to record.

## Tranches

- **`hand`** — this protocol. The only tranche headline numbers come from.
- **`public`** — a future bootstrap from a published weighed-meal dataset (e.g. Nutrition5k-style). Public images may appear in model training data, so public-tranche results are always reported separately, with a contamination caveat. Nothing to collect for it now; the enum just reserves the lane.

## Definition of done

- ≥150 hand-tranche meals meeting the distribution targets → the full benchmark runs for real and first-party numbers become publishable.
- Collection is incremental: the harness runs on any partial dataset, and a small smoke subset is useful from roughly the first dozen meals. Start small, record often.
