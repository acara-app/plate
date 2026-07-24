# Snap to Track Activation Funnel — Implementation Plan

Source PRD: `docs/prd-snap-to-track-activation.md` (Draft, 2026-07-24)

## Context

Snap to Track (`/tools/snap-to-track`) is Plate's most-visited public page, but the funnel breaks at its strongest value moment: after a visitor sees a real AI meal analysis, signing up discards the result (~400 results → 29 CTA clicks → nothing measurable in the last 30 days). This plan implements the PRD's activation funnel: a "Save this meal free" CTA stores the analysis as an expiring server-side draft, an opaque token + `url.intended` carry it through signup/login/verification/disclaimer, and an authenticated review page restores it for editing and logging through the existing health-entry pipeline.

**Scope (user-confirmed):** both MVP phases in a single PR, sequenced Phase 1 → Phase 2 internally, behind a feature flag that defaults off.

**Step 0 after approval:** save this plan as `docs/plan-snap-to-track-activation.md`, amend the PRD (`docs/prd-snap-to-track-activation.md`) to reflect the DB-backed draft store (its section 4 currently says "Laravel cache") and the `AnalysisDraft` naming, and save the naming-convention feedback (shared infrastructure named by capability, not by the first feature that needs it) to memory.

**Naming (user-confirmed):** the draft domain is feature-agnostic — `analysis_drafts` table, `AnalysisDraft` model — because the `source` column already anticipates other surfaces. Only user-facing surfaces (routes, controllers, pages, flag) carry the Snap to Track name.

## Key design decisions

1. **PRD amendment — drafts live in a DB table (`analysis_drafts`), not the cache.** The PRD says "Laravel cache", but `snap_to_track_draft_expired` + `draft_age_band` require the creation time of an *already-expired* draft, and distinguishing "expired" from "never existed" (enumeration noise) is impossible once a cache TTL evicts the entry. The default cache store is `database` anyway, so a table costs the same round-trips while adding a real CAS consume (`UPDATE … WHERE consumed_at IS NULL`), `cache:flush` immunity, and funnel queryability. Repo precedent: `app/Models/MobileTwoFactorChallenge.php` (`token_hash` char(64) unique + `MassPrunable`, daily `model:prune`). Matches the "app-native substrates" preference.
2. **Draft state machine:** created → claimed → consumed. Claim on first authenticated restore via conditional `UPDATE … WHERE token_hash = ? AND (user_id IS NULL OR user_id = ?) AND consumed_at IS NULL` (rejects cross-user). Consume via CAS `UPDATE … SET consumed_at, health_group_id WHERE token_hash = ? AND user_id = ? AND consumed_at IS NULL` inside the same `DB::transaction` as `RecordHealthSampleAction`; `affected = 0` ⇒ idempotent replay returning the stored `health_group_id` (no duplicate entry on double-click/back-button/concurrent save).
3. **Logical expiry ≠ deletion.** `expires_at` (60 min) is checked only on restore (GET). Consume (POST) does **not** check it — the TTL is a privacy control for *unclaimed* drafts; a user who restored at minute 59 can still save at minute 70. `prunable()` = `created_at < now()->subDay()` so expired rows survive 24 h for analytics + idempotent replays.
4. **Intended destination needs exactly two changes** (verified against code): (a) the Livewire `saveMeal` action seeds `session('url.intended')` with the review URL; (b) `app/Http/Middleware/EnsureDisclaimerAccepted.php:23` switches `to_route('disclaimer.show')` → `redirect()->guest(route('disclaimer.show'))` so the Google-new-user path re-seeds the destination the Socialite callback already consumed. Every auth controller already ends in `redirect()->intended(route('dashboard'))`; `EnsureEmailIsVerified` already re-seeds via `Redirect::guest()`; Fortify's `TwoFactorLoginResponse` uses `redirect()->intended()` (verified in vendor) so the 2FA branch survives too. Token lives in the URL path, making the review page refresh/back-button safe. Known unfixable edge: verification email opened on a different device (session-scoped intended) → recovery state covers it.
5. **Token:** `Str::random(64)` (~380 bits), stored only as `hash('sha256', $token)`. Fast hash is deliberate (high-entropy token, matches `MobileTwoFactorChallenge`). Draft creation is transitively capped by the existing 5/hr/IP analysis limit; the Livewire component reuses the token it already minted for the current result on repeat clicks.
6. **Authenticated module URL = `/app/snap-to-track/...`** because bare `/snap-to-track` is taken by the 301 → `/tools/snap-to-track` (`routes/web.php:41`), which must stay for SEO.
7. **Per-item breakdown goes additively into `health_sync_samples.metadata`** (JSON, already cast to array, unused for food today) under a namespaced `snap_to_track` key on each of the 4 grouped food rows. Aggregate fields unchanged ⇒ existing list/dashboard/sync consumers unaffected.
8. **Flag-off = zero behavior change:** old upsell CTA renders verbatim, authenticated routes 404, nav item hidden.

## Implementation steps

### A. Draft foundation (Phase 1)

1. **Migration** `create_analysis_drafts_table`: `id`, `token_hash` string(64) unique, `schema_version` unsignedSmallInteger, `source` string, `payload` json (serialized `FoodAnalysisData` — never the image), `user_id` nullable FK cascadeOnDelete, `claimed_at`/`consumed_at` nullable timestamps, `health_group_id` uuid nullable, `expires_at` timestamp indexed, timestamps.
2. **Model** `app/Models/AnalysisDraft.php` — mirror `MobileTwoFactorChallenge`: `MassPrunable` (`created_at < now()->subDay()`), `belongsTo(User)`, casts, helpers `isExpired()`, `isConsumed()`, `ageBand(): string` (lt_5m/lt_15m/lt_30m/lt_60m/expired). Plus `database/factories/AnalysisDraftFactory.php` (states: `claimed()`, `expired()`, `consumed()`).
3. **Enums**: `app/Enums/AnalysisDraftSource.php` (`PublicSnapToTrack = 'public_snap_to_track'`, `AuthenticatedSnapToTrack = 'authenticated_snap_to_track'`), `app/Enums/AnalysisDraftStatus.php` (`Restored | Expired | Invalid | Consumed | ClaimedByOther`), `app/Enums/ConfidenceBand.php` (`fromScore(int)`: ≥80 high, 50–79 medium, <50 low).
4. **Actions** at `app/Actions/` root like `AnalyzeFoodPhotoAction` (`final readonly`, `handle()`, no suffix per CLAUDE.md):
   - `app/Actions/CreateAnalysisDraft.php` — `handle(FoodAnalysisData $analysis, AnalysisDraftSource $source, ?int $claimedUserId = null): string`; inserts row, returns raw token.
   - `app/Actions/RestoreAnalysisDraft.php` — `handle(string $token, User $user)` → resolution DTO `app/Data/AnalysisDraftResolutionData.php` `{ status: AnalysisDraftStatus, draft: ?AnalysisDraft }`; missing row/schema mismatch → Invalid, expired → Expired, consumed → Consumed, conditional-UPDATE claim → Restored or ClaimedByOther.

### B. Flag + handoff (Phase 1)

5. **Flag**: `config/plate.php` → `'snap_to_track' => ['activation_funnel' => (bool) env('PLATE_SNAP_TO_TRACK_ACTIVATION_FUNNEL', false)]`; helper `snap_to_track_activation_enabled()` in `app/helpers.php` (mirror `enable_premium_upgrades()`); share `snapToTrackActivation` in `app/Http/Middleware/HandleInertiaRequests.php` + type in `resources/js/types/`; middleware `app/Http/Middleware/EnsureSnapToTrackActivationEnabled.php` (404 when off).
6. **Disclaimer fix**: `EnsureDisclaimerAccepted` → `redirect()->guest(route('disclaimer.show'))`; drop the `@codeCoverageIgnore`; regression test asserting `url.intended` survives the gate.
7. **Livewire SFC** `resources/views/pages/⚡snap-to-track.blade.php`:
   - `analyze()`: keep the DTO shape lossless — `$this->result = $analysis->toArray()` (preserves `analyzerVersion`, per-item `provenance`; existing camelCase assertions stay green).
   - New `saveMeal(string $intent, CreateAnalysisDraft $action)`: guard (flag on + result present); reuse already-minted token for this result; authenticated user → create claimed draft, redirect to review URL; guest → create draft, `session()->put('url.intended', route('snap-to-track.review', $token, absolute: false))`, `session()->put('snap_to_track.auth_path', $intent)`, redirect to `route($intent)` (`register`|`login`).
   - Result CTA block: `@if (snap_to_track_activation_enabled())` new primary "Save this meal free" (`wire:click="saveMeal('register')"`) + secondary "Log in" (`wire:click="saveMeal('login')"`), with Alpine `@click` firing `snap_to_track_save_click` (`source`, `authenticated`) and `snap_to_track_auth_started` (`auth_path`, guests only) via `acaraTrack`; `@else` current upsell verbatim.
   - `x-init` result event (line 410): send `source` + server-derived `confidence_band` instead of raw `confidence`.

### C. Restore + review page (Phase 1)

8. **Route** (inside the `['auth','verified',EnsureDisclaimerAccepted::class]` group at `routes/web.php:80`, nested `EnsureSnapToTrackActivationEnabled` + `prefix('app/snap-to-track')`): `GET review/{draft}` → `app/Http/Controllers/SnapToTrack/ShowSnapToTrackReviewController.php` (single-action, `#[CurrentUser]`), name `snap-to-track.review`.
9. **Controller** renders `snap-to-track/review` with `{ analysis, draftToken, state: 'restored'|'unavailable', meta }`; flashes analytics via the existing `use-flash-analytics.ts` bridge: `snap_to_track_draft_restored` (`draft_age_band`) or `snap_to_track_draft_expired` (Expired status only — Invalid fires nothing, preserving expired-vs-enumeration discrimination), plus `snap_to_track_auth_completed` (`auth_path`) when `session('snap_to_track.auth_path')` exists (then forget).
10. **Page** `resources/js/pages/snap-to-track/review.tsx` — `AppLayout` + shadcn/ui + Tailwind only: totals card, per-item rows, confidence band badge, estimate/not-for-dosing disclaimer, recovery state ("draft no longer available" + link to analyze another photo). Run `php artisan wayfinder:generate --with-form`.

### D. Save pipeline (Phase 2)

11. **`app/Data/HealthLogData.php`** — additive nullable fields (`foodItems` array, `confidence`, `analyzerVersion`, `foodSource`, `draftReference`); `toFoodSamples()` attaches `metadata.snap_to_track = { source, confidence, analyzer_version, draft_reference, items: [{name, portion, calories, protein, carbs, fat, provenance}] }` to each food row **only when `foodItems` is present** — existing callers unchanged.
12. **`app/Actions/LogReviewedMeal.php`** — `handle(AnalysisDraft $draft, HealthLogData $data, User $user)`: one `DB::transaction` { CAS consume; `affected = 0` ⇒ return existing `health_group_id`; else `RecordHealthSampleAction` (`HealthEntrySource::Web`), write back `health_group_id`, `DispatchAggregateUserUtcDatesAction` for the meal's UTC date }.
13. **FormRequest** `app/Http/Requests/SnapToTrack/StoreSnapToTrackMealRequest.php`: `items` required array 1–30; per item `name` required ≤100, `portion` nullable ≤100, `calories`/`protein`/`carbs`/`fat` numeric min:0; `measured_at` required date; `notes` nullable ≤500; `withValidator` sums items against the `HealthEntryRequest` caps (calories ≤5000, carbs ≤1000, protein ≤500, fat ≤500). Covers the PRD adversarial cases (negative/excessive/empty/missing-carbs→0).
14. **Controller** `StoreSnapToTrackMealController` (`POST review/{draft}`, name `snap-to-track.review.store`): resolve + ownership check, build `HealthLogData` from edited items (server re-sums authoritatively), call `LogReviewedMeal`, redirect to `snap-to-track.index` with saved-entry flash + `snap_to_track_meal_logged` analytics flash (`source`, `items_count`). Validation/storage failure leaves the draft unconsumed and the form repopulated.

### E. Authenticated analyzer + module (Phase 2)

15. **Routes**: `GET ''` → `ShowSnapToTrackController` (name `snap-to-track.index`); `POST analyze` → `AnalyzeSnapToTrackPhotoController` with a named per-user limiter (`RateLimiter::for('snap-to-track-analyze', 5/hr by user id)` in `AppServiceProvider`).
16. **`AnalyzeSnapToTrackPhotoController`** + `AnalyzeSnapToTrackPhotoRequest` (`photo` required|image|max:10240): standard file upload (no Livewire temp upload, no Turnstile for authenticated users), base64 → `AnalyzeFoodPhotoAction`, create **claimed** draft (`AuthenticatedSnapToTrack`), delete the upload in `finally`, flash `snap_to_track_result_viewed` (`source`, `items_count`, `confidence_band`), redirect to the review route — the authenticated result always enters review before logging (Story 6).
17. **Pages**: `snap-to-track/index.tsx` (uploader via Inertia `<Form>` + Wayfinder `.form()`, post-save confirmation banner with "Open saved entry" → health-entries + "Analyze another"); make `review.tsx` editable — item rows (name/portion/kcal/P/C/F inputs), add/remove, client-side live totals, editable timestamp + notes, labeled controls/keyboard nav/mobile widths, submit via `StoreSnapToTrackMealController.form(draftToken)`. Shared components under `resources/js/pages/snap-to-track/components/`.
18. **Nav + i18n**: `app-sidebar.tsx` `getMainNavItems` entry gated on `snapToTrackActivation` shared prop (lucide camera/scan icon); strings for the React pages + `sidebar.nav.snap_to_track` in `lang/{en,fr,mn}/common.php`. Public SFC CTA copy stays hardcoded English like the rest of that page.

### F. Tests (Pest, throughout — not a trailing phase)

- `tests/Feature/Actions/`: `CreateAnalysisDraftTest` (sha256 storage, no raw token persisted, TTL, claimed-at-creation, payload excludes image); `RestoreAnalysisDraftTest` (claim, ClaimedByOther, Expired, Consumed, Invalid); `LogReviewedMealTest` (4 grouped rows sharing `group_id`, namespaced metadata, consume marked, **idempotent replay** — second call adds no rows and returns same group id, aggregate dispatched once via `Queue::assertPushed(AggregateUserDayJob)`).
- `tests/Feature/Controllers/SnapToTrack/`: `ShowSnapToTrackReviewControllerTest` (Inertia component/props/state, correct analytics flashes incl. no-event-on-Invalid, flag-off 404); `StoreSnapToTrackMealControllerTest` (happy path, duplicate POST, adversarial validation, save-after-expiry-once-claimed works, cross-user rejected, HTML-in-name stored raw); `AnalyzeSnapToTrackPhotoControllerTest` (fake `FoodPhotoAnalyzerAgent`, claimed draft, temp file deleted, throttle, flag-off 404); contract test proving one `FoodAnalysisData` yields identical review props from the public-draft and authenticated paths.
- `IntendedDestinationTest` — end-to-end per auth path: email register → verification.notice re-seeds → signed verify → review; plain login → review; Google new user (anonymous-class Socialite fake, per `GoogleOAuthTest`) → callback → disclaimer gate preserves intended → accept → review; Google existing → review; already-authenticated saveMeal → straight to review.
- `EnsureDisclaimerAcceptedTest` — `url.intended` survives the gate (regression for the fix).
- Update `tests/Feature/Pages/SnapToTrackTest.php`: existing upsell-copy tests remain the flag-off default; add flag-on tests (new CTA copy, guest saveMeal → register/login redirect + seeded intended + draft row, authenticated saveMeal → review redirect, result_viewed band props).
- Existing suites expected green: `AnalyzeFoodPhotoActionTest` (action untouched), `RecordHealthSampleActionTest` / food-sample tests (`HealthLogData` change is additive).

## Verification

1. `vendor/bin/pest tests/Feature/SnapToTrack tests/Feature/Pages/SnapToTrackTest.php tests/Feature/DisclaimerTest.php tests/Feature/Controllers/HealthEntry` then the full `php artisan test --compact`.
2. `vendor/bin/pint --dirty --format agent`; `php artisan wayfinder:generate --with-form`; `npm run build`; Larastan if configured in CI.
3. Manual QA on Herd (flag on in `.env`): analyze → Save free → register (email verify same browser) → review lands with items → edit → save → entry appears in `/health-entries` with metadata; repeat for login, Google-new (disclaimer), Google-existing, already-authenticated; verify duplicate save via back-button creates no second entry; verify Umami payloads (network tab) contain only enums/counts/bands.
4. Safety checks before PR: `rg "acara-app/acara-core|Acara\\\\AcaraCore" app config routes tests resources composer.json` clean; flag-off run shows the page byte-identical to today.
5. Rollout: merge with `PLATE_SNAP_TO_TRACK_ACTIVATION_FUNNEL=false`; enable on staging first; after enabling in prod, watch completed analyses (>-10%), draft-restore success ≥95%, restore→save ≥70%, and expiry frequency before touching the 60-min TTL.

## Entry points (added 2026-07-24)

Beyond the sidebar item, two more flag-gated entry points link to `snap-to-track.index` via `ShowSnapToTrackController().url`, both conditional on the `snapToTrackActivation` shared prop:

- **Dashboard** (`resources/js/pages/dashboard.tsx`): an emerald launcher pill directly under the chat composer (gradient `ScanLine` tile, `snap_to_track.launcher.*` copy, chevron affordance). One pill, not a card grid, to preserve the launcher page's single-purpose composition.
- **Chat conversation** (`resources/js/pages/chat/create-chat.tsx`): a `ScanLine` icon button in the floating header `ButtonGroup` beside new-chat/keep/pin, tooltip labelled with `sidebar.nav.snap_to_track`. The composer paperclip (in-chat AnalyzePhoto) is intentionally left untouched to avoid forking the photo mental model.
