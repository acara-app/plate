# Caffeine Drinks Dataset

This directory contains the vetted CSV dataset that seeds the `caffeine_drinks` table for the caffeine calculator tool.

## File

- `caffeine_drinks.csv` — Common caffeinated beverages with typical caffeine content per serving.

## Source & License

**Source:** USDA FoodData Central (FDC)
**License URL:** https://fdc.nal.usda.gov/
**Policy reference:** https://www.usda.gov/policies-and-links

The U.S. Department of Agriculture's FoodData Central is a work of the U.S. Government and is therefore in the **public domain** within the United States under 17 U.S.C. § 105. It carries no copyright restrictions and is explicitly free to use for any purpose, **including commercial use**, with no required permission. Attribution is appreciated but not required.

The `attribution` column in the CSV credits "USDA FoodData Central, U.S. Department of Agriculture (public domain)" for each row.

## Why not the Kaggle "Caffeine Content of Drinks" dataset?

We considered the Kaggle dataset "Caffeine Content of Drinks" as a source. We could not confirm an unambiguous, commercial-OK license attached to that dataset (Kaggle datasets vary widely — CC0, CC BY-SA, CC BY-NC, "Other," or unspecified — and even when listed as CC0, the underlying records are sometimes scraped from third-party sites with their own terms). To avoid any commercial-use risk for the calculator, we chose the USDA FoodData Central data, which is unambiguously public domain.

If a future contributor confirms the Kaggle dataset is genuinely CC0 (and the upstream records permit redistribution), the CSV here can be replaced or extended; record the new license URL in this README and in the `license_url` column of the affected rows.

## Schema

The CSV columns map 1:1 to the `caffeine_drinks` migration:

| Column        | Notes                                              |
| ------------- | -------------------------------------------------- |
| `name`        | Display name of the drink                          |
| `slug`        | Unique kebab-case identifier                       |
| `category`    | `coffee`, `tea`, `soda`, `energy`, `other`         |
| `volume_oz`   | Typical serving size in fluid ounces               |
| `caffeine_mg` | Caffeine content per `volume_oz` serving           |
| `source`      | Origin of the figure (e.g. "USDA FoodData Central") |
| `license_url` | Public URL describing the source's license terms   |
| `attribution` | Human-readable credit string for the source        |
| `verified_at` | ISO date when the row's caffeine value was verified against the source |

Values reflect typical/averaged servings; brand-specific products vary by formulation and region.
