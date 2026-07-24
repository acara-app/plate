# Product Requirements Document: Snap to Track Activation Funnel

**Status:** Draft  
**Owner:** Acara Plate  
**Last updated:** July 24, 2026

## 1. Executive Summary

### Problem Statement

Snap to Track is Acara Plate's most popular page, but its organic traffic is not connected to a durable product experience. Visitors can analyze a meal and see useful nutrition estimates, but signing up discards the analysis and sends them through a generic authentication and onboarding flow.

This break occurs immediately after the product's strongest value moment. During the latest 30-day period, approximately 400 analysis results produced 29 signup CTA clicks, but there is no measurement or workflow connecting those clicks to a saved meal.

### Proposed Solution

Keep the public analyzer free and preserve its current search experience. After showing the analysis, offer a concrete “Save this meal free” action that stores the result as a temporary server-side draft, carries it through signup or login, and restores it in a dedicated authenticated Snap to Track module.

The authenticated user can review and correct the detected foods before saving the meal to their health history. The first release will optimize activation rather than require payment. Pricing and usage limits will be decided after the funnel produces reliable activation data.

### Success Criteria

- At least 5% of public result viewers save their first meal within the same acquisition journey.
- At least 15% of public result viewers select “Save this meal free.”
- At least 40% of visitors who start authentication from a result complete authentication.
- At least 70% of authenticated users who successfully restore a draft save the meal.
- At least 95% of valid, unexpired drafts are restored successfully after authentication.
- The public page retains its current search visibility and does not reduce completed analyses by more than 10%.

The baseline measurement window and all target comparisons will use complete 30-day cohorts.

## 2. User Experience & Functionality

### User Personas

#### Organic nutrition researcher

A mobile visitor arriving primarily from Google who wants an immediate calorie and macronutrient estimate without creating an account first.

#### Newly activated tracker

A visitor who received a useful analysis and is willing to create an account if the result can be saved without repeating the work.

#### Existing Acara Plate user

An authenticated user who wants photo-based meal analysis integrated directly with their existing health history.

### User Stories

#### Story 1: Analyze without an account

As an organic visitor, I want to analyze one food photo without signing up so that I can evaluate the tool before committing.

**Acceptance criteria**

- The public `/tools/snap-to-track` page remains available without authentication.
- The upload, Turnstile verification, analysis, result breakdown, confidence indicator, and “Analyze another photo” behavior continue to work.
- The page does not introduce a signup gate before displaying the result.
- Temporary uploaded photos continue to be deleted after analysis or failure.
- The public page retains its existing metadata, structured data, canonical URL, and indexable explanatory content.

#### Story 2: Save a useful result

As a visitor who received an analysis, I want to save it so that I do not lose the calorie and macronutrient breakdown.

**Acceptance criteria**

- A successful result displays “Save this meal free” as the primary CTA.
- “Log in” is available as the secondary authentication action.
- Selecting either action creates a temporary server-side draft before leaving the result.
- The draft contains the detected items, portions, nutrition values, provenance, totals, confidence score, and creation time.
- The draft does not contain the uploaded image or other unnecessary personal data.
- The browser receives only an opaque draft token; nutrition data is not placed in the URL.
- The draft expires 60 minutes after creation.

#### Story 3: Continue through authentication

As a visitor creating or accessing an account, I want to return to my analyzed meal after authentication so that I do not repeat the scan.

**Acceptance criteria**

- Registration and login preserve the intended authenticated Snap to Track destination.
- Email verification, disclaimer acceptance, and supported social authentication preserve the same destination.
- A valid draft is restored after all required authentication steps are complete.
- Authentication errors do not delete the draft.
- An expired, invalid, or consumed token displays a recovery state with a link to analyze another photo.
- Draft tokens are sufficiently random to prevent enumeration.

#### Story 4: Review the AI estimate

As an authenticated user, I want to correct the AI result before saving it so that my health history reflects what I actually ate.

**Acceptance criteria**

- The authenticated Snap to Track page displays every detected item and the meal totals.
- Users can edit each item's name, portion, calories, protein, carbohydrates, and fat.
- Users can add and remove items.
- Meal totals recalculate immediately from the current item values.
- Users can edit the meal timestamp and add optional notes.
- The UI clearly states that values are estimates and are not appropriate for medication or insulin-dosing decisions.
- The page is usable at mobile widths and supports keyboard navigation and labeled form controls.

#### Story 5: Log the meal

As an authenticated user, I want to confirm the reviewed meal so that it becomes part of my health history.

**Acceptance criteria**

- Saving records a grouped food health entry through the existing health-entry pipeline.
- The saved entry includes total calories, protein, carbohydrates, fat, timestamp, notes, and the structured per-item breakdown.
- Saving triggers the existing daily aggregate refresh.
- A successful save consumes the draft and cannot create a duplicate entry if the request is retried.
- A failed validation or storage request leaves the reviewed meal available for correction and retry.
- After saving, the user sees confirmation and can open the saved entry or analyze another meal.

#### Story 6: Analyze from inside the app

As an existing user, I want the same photo analyzer inside the authenticated app so that I can analyze and log meals in one workflow.

**Acceptance criteria**

- The app provides a dedicated authenticated Snap to Track navigation destination.
- Authenticated users can upload and analyze a new food photo without visiting the marketing page.
- The authenticated module uses the same underlying analysis action and output contract as the public tool.
- The authenticated result always enters the review state before it can be logged.

#### Story 7: Measure the activation funnel

As the product team, I want to measure each funnel stage so that pricing and conversion decisions are based on observed behavior.

**Acceptance criteria**

- Analytics distinguish public visitors, newly authenticated users, and existing authenticated users without sending personal or nutrition data.
- The funnel can report result view, save click, authentication start, authentication completion, draft restoration, and meal saved.
- Duplicate browser events do not inflate unique funnel completions.
- A complete 30-day cohort can be compared with the pre-launch baseline.

### Non-Goals

- Charging for the first saved meal.
- Defining subscription pricing, plan packaging, or recurring scan allowances.
- Blocking the public result behind registration.
- Retaining public food photos after analysis.
- Automatically saving AI output without explicit review and confirmation.
- Adding barcode scanning, label OCR, voice logging, or restaurant-menu lookup.
- Redesigning the general health-entry history experience.
- Changing the AI model or claiming improved nutritional accuracy as part of this project.

## 3. AI System Requirements

### Tool Requirements

- Continue using `AnalyzeFoodPhotoAction` as the shared analysis boundary for public and authenticated experiences.
- Return a typed analysis result containing:
  - Detected food items.
  - Portion description per item.
  - Calories, protein, carbohydrates, and fat per item.
  - Nutrition provenance per item.
  - Meal-level totals.
  - Confidence score.
  - Analyzer version when available.
- Preserve the current upload validation, Turnstile protection, request throttling, pinned model configuration, and temporary-photo deletion.
- Treat the model output as an editable estimate, never as a directly committed health record.
- Do not send the analysis contents to Umami or other product analytics services.

### Evaluation Strategy

- Existing food-photo analyzer tests must continue validating structured output, reference matching, error handling, and rate limiting.
- Add contract tests proving the public and authenticated modules interpret the same analysis result identically.
- Add calculation tests proving edited items produce the expected meal totals.
- Add adversarial validation cases for negative values, excessive values, missing carbohydrates, empty item lists, malformed drafts, and stale analyzer output.
- Confirm that confidence and provenance are preserved from analysis through review but do not prevent users from correcting the values.
- This feature does not change the model, so the existing Golden Plate accuracy benchmark remains the quality gate for analysis accuracy.

## 4. Technical Specifications

### Architecture Overview

The public Livewire component and authenticated Inertia module will share backend Actions and typed data objects while retaining UI implementations appropriate to each surface.

1. The public Livewire page uploads and analyzes the temporary photo.
2. The shared analysis Action returns a typed result.
3. The public page renders the result without requiring authentication.
4. Selecting “Save this meal free” invokes an Action that stores an expiring draft in the database-backed analysis draft store.
5. The Action creates an opaque token and sets the authenticated draft-review URL as the intended destination.
6. The visitor completes registration, login, verification, and disclaimer acceptance as required.
7. An authenticated controller resolves the draft and renders the dedicated Inertia React review page.
8. The user edits and submits the meal.
9. A reusable logging Action records the grouped health entry, refreshes daily aggregates, consumes the draft, and redirects to confirmation.

Existing authenticated users skip the authentication steps and open the review page immediately.

### Public Interfaces and Data Contracts

#### Analysis result

Introduce a typed data object for the shared analyzer output instead of passing an inline array between the public component, cache, controller, and React page.

Required fields:

- `items`
- `totalCalories`
- `totalProtein`
- `totalCarbs`
- `totalFat`
- `confidence`
- `analyzerVersion`

Each item requires:

- `name`
- `portion`
- `calories`
- `protein`
- `carbs`
- `fat`
- `provenance`

#### Analysis draft

Introduce a typed draft object containing:

- Draft schema version.
- Analysis result.
- Source identifier set to `public_snap_to_track`.
- Creation and expiration timestamps.
- Optional authenticated user ID after the draft is claimed.
- Consumption timestamp or equivalent atomic consumed state.

Drafts persist in the feature-agnostic `analysis_drafts` table (`AnalysisDraft` model). The source identifier distinguishes producing surfaces, and expiry is logical (checked on restore) so expired drafts remain observable for funnel analytics until pruned.

#### Authenticated routes

Add authenticated routes for:

- Displaying the Snap to Track module.
- Analyzing a newly uploaded photo.
- Restoring a public analysis draft.
- Saving the reviewed meal.

Routes must use the existing `auth`, `verified`, and disclaimer middleware conventions. Wayfinder bindings must be regenerated with form support.

#### Health-entry storage

- Use the existing `RecordHealthSampleAction` and aggregate refresh workflow.
- Store totals using the established food health metrics.
- Store the item breakdown as structured metadata on the grouped health entry.
- Use `HealthEntrySource::Web`.
- Use an idempotency value derived from the draft when saving a restored public result.
- Do not require a new meal-history storage system for this release.

### Integration Points

- **Livewire 4:** Public upload, analysis, result rendering, and draft-creation action.
- **Inertia 3 and React 19:** Authenticated analyzer and editable review form.
- **Laravel authentication:** Intended redirects across login, registration, verification, disclaimer acceptance, and social login.
- **Analysis draft store:** Expiring `analysis_drafts` database records with atomic claim and consume operations.
- **Health entries:** Existing request validation, recording Action, grouped sample metadata, and aggregate jobs.
- **Umami:** Anonymous funnel events and non-sensitive event properties.
- **Wayfinder:** Typed routes and form definitions for the authenticated module.

### Analytics Specification

Track the following events:

| Event | Trigger | Allowed properties |
|---|---|---|
| `snap_to_track_result_viewed` | Public or authenticated result becomes visible | `source`, `items_count`, `confidence_band` |
| `snap_to_track_save_click` | User selects the result CTA | `source`, `authenticated` |
| `snap_to_track_auth_started` | Unauthenticated user is sent to authentication | `auth_path` |
| `snap_to_track_auth_completed` | User returns authenticated with the draft journey intact | `auth_path` |
| `snap_to_track_draft_restored` | Authenticated review loads a valid public draft | `draft_age_band` |
| `snap_to_track_meal_logged` | Reviewed meal is successfully saved | `source`, `items_count` |
| `snap_to_track_draft_expired` | Restoration encounters an expired draft | `draft_age_band` |

Email addresses, user IDs, food names, nutrition values, notes, draft tokens, and image information must never be included.

### Security & Privacy

- Generate draft tokens using a cryptographically secure random source with at least 128 bits of entropy.
- Hash tokens for storage lookup so raw bearer tokens are never persisted.
- Expire drafts after 60 minutes and remove them after successful consumption.
- Bind a claimed draft to the authenticated user before saving.
- Use an atomic lock or consume operation to prevent duplicate logs.
- Validate all restored and edited values server-side using the existing health-entry limits.
- Escape user-edited food names and notes in every rendered context.
- Never serialize uploaded images into the draft, session, URL, analytics, or health-entry metadata.
- Retain the existing medical disclaimer and AI accuracy boundary.
- Do not introduce an `acara-app/acara-core` dependency or private namespace into `main`.

## 5. Risks & Roadmap

### Phased Rollout

#### MVP Phase 1: Measurement and durable handoff

- Replace the result upsell with “Save this meal free.”
- Add the typed analysis result and expiring server-side draft.
- Preserve the draft through authentication and required account steps.
- Restore the result in an authenticated review page.
- Add the complete funnel instrumentation.
- Release behind a configurable feature flag.

**Exit criteria**

- Draft restoration succeeds in at least 95% of automated end-to-end cases.
- No uploaded photos persist after public analysis.
- Analytics can measure every stage through draft restoration.

#### MVP Phase 2: Authenticated analyzer and logging

- Add photo upload and analysis directly to the authenticated module.
- Add item editing, total recalculation, meal confirmation, and structured logging.
- Add idempotent draft consumption and saved-entry confirmation.
- Expose Snap to Track in authenticated navigation.

**Exit criteria**

- At least 70% of users who restore a valid draft can save it without an application error.
- All targeted backend, Livewire, Inertia, TypeScript, and browser tests pass.
- The full result-to-first-log funnel is measurable.

#### Version 1.1: Funnel optimization

- Review the first complete 30-day cohort.
- Test CTA copy, result-page explanation, authentication choice, and review-page friction.
- Improve the weakest measured funnel stage.
- Evaluate onboarding shortcuts for users whose first intent is saving a meal.

#### Version 2.0: Monetization experiment

- Use activation, repeat usage, analysis cost, and retention data to choose packaging.
- Evaluate recurring free allowances, premium scan limits, or history-based benefits.
- Define pricing only after the product team can measure repeat photo-analysis behavior and downstream retention.

### Technical Risks

#### Authentication loses the intended destination

Multi-step verification, disclaimer, and social-login flows may overwrite the intended URL.

**Mitigation:** Add end-to-end feature tests for every supported authentication path and centralize draft destination preservation.

#### Draft expires during onboarding

A user may take longer than 60 minutes to complete authentication.

**Mitigation:** Display a clear expiration recovery state. Measure expiration frequency before changing the privacy-oriented TTL.

#### Duplicate health entries

Retries, back-button behavior, or concurrent requests could save the same draft multiple times.

**Mitigation:** Use an atomic server-side consume operation and an idempotency identifier tied to the draft.

#### Public traffic or SEO declines

Changing the public experience could reduce completed scans or search performance.

**Mitigation:** Preserve the URL, page metadata, structured data, explanatory content, and ungated result. Monitor analyses, organic entries, and Search Console performance during rollout.

#### Event counts are inflated

The current upload-click event volume exceeds page visits, suggesting repeated interaction events.

**Mitigation:** Define unique funnel completion using visitor/session-level events and server-confirmed milestones rather than raw click totals.

#### Structured item metadata conflicts with existing health-entry consumers

Current views may expect only aggregate food metrics.

**Mitigation:** Add metadata additively, keep aggregate fields unchanged, and test existing list, edit, dashboard, and synchronization behavior.

#### Analysis cost grows after activation improves

The authenticated module may increase repeat analysis usage before pricing is established.

**Mitigation:** Retain existing throttles, measure per-analysis cost and repeat usage, and make future entitlement rules configurable without placing them in the MVP.

