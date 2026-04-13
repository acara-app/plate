# Changelog

All notable changes to Acara Plate will be documented in this file.

## v0.12.0 - 2026-04-13

### Features

- **Blog post system with multi-locale support** — Full blog/Post system with multi-locale, hreflang, canonical URLs, and sitemap integration (#232)
- **Health aggregation pipeline** — V2 sleep events API, UTC-based daily aggregation pipeline, `AggregateHealthDailyCommand`, `RebuildHealthDailyAggregatesCommand`, `RevalidateHealthAggregatesCommand` (#224)
- **Health sync rewrite to UTC** — Rewrote sync and daily aggregation to use UTC consistently (#234)
- **Medication dose events** — Support medication dose events and metadata from iOS app (#216)
- **Medication library snapshots** — Support medication library snapshots from iOS app (#217)
- **Meal plan tool improvements** — Capping feedback, absolute URLs, custom_prompt passthrough, expanded mode prompt (#218)

### Fixes

- Fix `CannotCastDate` error for ISO 8601 Z-suffix dates (#223)
- Fix CarbonImmutable usage for `HealthLogData::measuredAt` (#223)
- Fix `Carbon::parse` vs `Date::parse` for mutable Carbon instances
- Fix date cast microsecond parsing and empty health sample errors (#219)
- Fix unclickable meal plan link in Telegram chat (#221)
- Fix login page missing password reveal toggle (#220)
- Fix `translation_group` and `xDefaultUrl` issues for multi-locale posts (#232)
- Fix multi-locale SEO issues with hreflang, canonical URLs, and localization (#232)

### Refactors

- Rename `app/DataObjects` to `app/Data` to match Spatie Laravel Data conventions (#233)
- Rename Blog to Post across the codebase (#232)
- Remove V1 sync API and consolidate to V2 (#227)
- Use Content scopes instead of static `food_sitemap.xml` in `SubmitSitemapsToIndexNowCommand` (#231)
- Remove scattered `Log::` calls and simplify console output (#230)
- Refactor service classes (#229)
- Simplify action classes with early returns and cleanup (#228)
- Refactor metadata handling into dedicated DataObject classes (#216)
- Clean up all feature tests with `covers()`, chained expects, and consistent `it()` syntax (#226)
- Clean up all unit tests with `covers()`, chained expects, and consistent `it()` syntax (#225)
- Rename `HealthMetricDescriptor` to `HealthMetricDescriptorData` for consistency

### Chores

- Update `defstudio/telegraph` to official Laravel 13 compatible release (#222)
- Disable Telegraph webhook debug logging by default
- Add `nunomaduro/pao` dev dependency
- Drop FR locale support temporarily
- Clean pending lint and type narrowing changes
