<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Facades\Date;
use App\Models\ReferenceFood;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

#[Description('Import a USDA FoodData Central JSON export into the reference_foods table.')]
#[Signature('nutrition:import-references {path : Path to the FoodData Central JSON export} {--source=usda} {--type=foundation} {--release= : Human-readable release identifier; derived from the filename when omitted}')]
final class ImportReferenceFoodsCommand extends Command
{
    private const string ENERGY_KCAL = '208';

    private const string ENERGY_ATWATER_GENERAL = '957';

    private const string ENERGY_ATWATER_SPECIFIC = '958';

    private const string PROTEIN = '203';

    private const string FAT = '204';

    private const string CARBS = '205';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! File::exists($path)) {
            $this->error(sprintf('File not found: %s', $path));

            return self::FAILURE;
        }

        $foods = $this->readFoods($path);

        if ($foods === null) {
            $this->error('Could not find a recognizable food list in the export (expected a FoundationFoods or SRLegacyFoods key).');

            return self::FAILURE;
        }

        $source = (string) $this->option('source');
        $type = (string) $this->option('type');
        $release = $this->resolveRelease($path, $type);

        $imported = 0;
        $skipped = 0;

        $this->withProgressBar($foods, function (mixed $food) use ($source, $type, $release, &$imported, &$skipped): void {
            if (! is_array($food) || ! isset($food['fdcId'])) {
                $skipped++;

                return;
            }

            DB::transaction(fn () => $this->upsert($food, $source, $type, $release));
            $imported++;
        });

        $this->newLine(2);
        $this->info(sprintf('Imported %d reference foods (release "%s"). Skipped %d entries without an id.', $imported, $release, $skipped));

        return self::SUCCESS;
    }

    /**
     * @return list<mixed>|null
     */
    private function readFoods(string $path): ?array
    {
        try {
            $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        foreach (['FoundationFoods', 'SRLegacyFoods', 'SurveyFoods', 'BrandedFoods'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_values($payload[$key]);
            }
        }

        return array_is_list($payload) ? $payload : null;
    }

    /**
     * @param  array<array-key, mixed>  $food
     */
    private function upsert(array $food, string $source, string $type, string $release): void
    {
        $description = $this->stringValue($food['description'] ?? null);
        $nutrients = $this->mapNutrients($food['foodNutrients'] ?? []);

        ReferenceFood::query()->updateOrCreate(
            ['source' => $source, 'external_id' => $this->stringValue($food['fdcId'] ?? null)],
            [
                'data_type' => $type,
                'description' => $description,
                'match_name' => ReferenceFood::normalizeName($description),
                'food_category' => $this->foodCategory($food),
                'calories_per_100g' => $this->energy($nutrients),
                'protein_per_100g' => $this->nutrientAmount($nutrients, self::PROTEIN),
                'carbs_per_100g' => $this->nutrientAmount($nutrients, self::CARBS),
                'fat_per_100g' => $this->nutrientAmount($nutrients, self::FAT),
                'nutrients' => $nutrients,
                'release' => $release,
                'publication_date' => $this->publicationDate($food),
            ],
        );
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param  array<string, array{name: string|null, amount: float|null, unit: string|null}>  $nutrients
     */
    private function nutrientAmount(array $nutrients, string $number): ?float
    {
        return $nutrients[$number]['amount'] ?? null;
    }

    /**
     * @return array<string, array{name: string|null, amount: float|null, unit: string|null}>
     */
    private function mapNutrients(mixed $foodNutrients): array
    {
        if (! is_array($foodNutrients)) {
            return [];
        }

        $map = [];

        foreach ($foodNutrients as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if (! is_array($entry['nutrient'] ?? null)) {
                continue;
            }
            $number = $entry['nutrient']['number'] ?? null;

            if (! is_string($number) && ! is_int($number)) {
                continue;
            }

            $amount = $entry['amount'] ?? null;

            $map[(string) $number] = [
                'name' => isset($entry['nutrient']['name']) ? $this->stringValue($entry['nutrient']['name']) : null,
                'amount' => is_numeric($amount) ? (float) $amount : null,
                'unit' => isset($entry['nutrient']['unitName']) ? $this->stringValue($entry['nutrient']['unitName']) : null,
            ];
        }

        return $map;
    }

    /**
     * @param  array<string, array{name: string|null, amount: float|null, unit: string|null}>  $nutrients
     */
    private function energy(array $nutrients): ?float
    {
        return $this->nutrientAmount($nutrients, self::ENERGY_KCAL)
            ?? $this->nutrientAmount($nutrients, self::ENERGY_ATWATER_GENERAL)
            ?? $this->nutrientAmount($nutrients, self::ENERGY_ATWATER_SPECIFIC);
    }

    /**
     * @param  array<array-key, mixed>  $food
     */
    private function foodCategory(array $food): ?string
    {
        $category = $food['foodCategory'] ?? null;

        if (is_array($category) && isset($category['description'])) {
            return $this->stringValue($category['description']);
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $food
     */
    private function publicationDate(array $food): ?Carbon
    {
        $date = $food['publicationDate'] ?? null;

        if (! is_string($date) || $date === '') {
            return null;
        }

        try {
            return Date::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveRelease(string $path, string $type): string
    {
        $release = (string) ($this->option('release') ?? '');

        if ($release !== '') {
            return $release;
        }

        preg_match('/(\d{4}-\d{2}-\d{2})/', basename($path), $matches);
        $date = $matches[1] ?? null;

        return mb_trim(sprintf('USDA %s%s', ucfirst($type), $date !== null ? ' ' . $date : ''));
    }
}
