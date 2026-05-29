# Health metrics pipeline

How Apple HealthKit samples (via the Health Sync iOS app) become queryable, aggregated health data,
and the rules to follow when adding or changing a metric type.

## Flow

1. The iOS app POSTs samples to `POST /v2/sync/health-entries` as `{type, value, unit, date, ...}`,
   where `type` is a camelCase identifier (e.g. `fiber`, `vitaminD`, `sodium`).
2. `SyncMobileHealthEntriesAction` converts each value to its canonical unit via
   `HealthMetricUnitConverter::toCanonical()` and stores it in `health_sync_samples` (canonical `unit`,
   incoming unit kept in `original_unit`). `type_identifier` is a free-form string column — unknown
   types are stored, not rejected.
3. `AggregateHealthDailySamplesAction` rolls samples into `health_daily_aggregates` using the registry
   descriptor (aggregation category + function).
4. The AI tools `get_health_data` / `get_health_summary` read this data, filtered by
   `HealthSyncSample::categoryFor()` (e.g. `type: "food"`).

## Sources of truth (keep in sync)

Three places describe a type. When adding or changing a HealthKit type, update each that applies:

- `config/health_metrics.php` — the registry: aggregation category + function, display/canonical
  units, and `unit_conversions`. Authoritative for aggregation and unit handling; covers all ~99 types.
- `app/Enums/HealthSyncType.php` — the narrower set of types that need host-integration behavior
  (user-characteristic mapping, metadata normalization, legacy health-log entry fields). Only types
  needing that behavior belong here.
- `HealthSyncSample::categoryFor()` — maps a type identifier to the AI-tool category string (`food`,
  `glucose`, `vitals`, …). Non-enum types are categorized here; this drives `type: "food"` filtering.

## Unit conversion

`HealthMetricUnitConverter` converts an incoming `unit` to the registry `canonical_unit`. If the unit
is neither canonical nor present in that type's `unit_conversions`, the sample is **dropped at ingest**
(reported as `samples_dropped` in the sync response).

So every nutrient must declare `unit_conversions` covering the units clients actually send. Several
micronutrients arrive in a different unit than their canonical one: six vitamins (A, D, K, B12, folate,
biotin) and the trace minerals (selenium, chromium, molybdenum, iodine) are canonical `mcg` but arrive
as `mg` or `µg`. The `$gCanonical` / `$mgCanonical` / `$mcgCanonical` tables in `config/health_metrics.php`
bridge the mass ladder, and `HealthMetricUnitConverter::normalizeUnit()` folds the microgram spellings
(`µg`/`μg`/`ug`) onto `mcg`.

## Nutrition surfacing

All dietary types map to the `food` category in `categoryFor()`, so `get_health_summary(type: "food")`
returns the full nutrient panel (calories, macros, fat subtypes, fiber, sugar, sodium, potassium,
vitamins, minerals) in one call. Individual nutrients remain queryable by exact identifier
(e.g. `type: "vitaminD"`).
