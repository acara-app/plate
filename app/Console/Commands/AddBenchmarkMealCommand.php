<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\NutrientValues;
use App\Enums\Benchmark\CameraAngle;
use App\Enums\Benchmark\DishType;
use App\Enums\Benchmark\Lighting;
use App\Enums\Benchmark\Tranche;
use App\Enums\Benchmark\TruthScope;
use App\Enums\Benchmark\TruthSource;
use App\Models\BenchmarkMeal;
use App\Models\BenchmarkMealItem;
use BackedEnum;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\File as PhotoFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * @phpstan-type GatheredItem array{name: string, visible: bool, weight_g: float, kcal_per_100g: float, carbs_per_100g: float, protein_per_100g: float, fat_per_100g: float, truth_source: int|string, truth_ref: string|null}
 */
#[Description('Interactively record a golden-plate benchmark meal: ground truth into the database, photo onto the benchmark disk.')]
#[Signature('benchmark:add-meal {photo : Path to the meal photo (jpg, jpeg, or png)}')]
final class AddBenchmarkMealCommand extends Command
{
    private const float WEIGHT_SUM_TOLERANCE = 0.05;

    private const float ATWATER_TOLERANCE = 0.25;

    public function handle(): int
    {
        $photoPath = (string) $this->argument('photo');

        if (! File::exists($photoPath)) {
            $this->error(sprintf('Photo not found: %s', $photoPath));

            return self::FAILURE;
        }

        $extension = mb_strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $this->error('Photo must be a jpg, jpeg, or png file (iPhone: use "Most Compatible" or export as JPEG).');

            return self::FAILURE;
        }

        $meal = $this->gatherMeal();
        $items = [];

        if ($meal['truth_scope'] === TruthScope::PerItem->value) {
            $items = $this->gatherItems();

            if (! $this->confirmItemWeights($items, $meal['total_weight_g'])) {
                $this->info('Meal discarded. Nothing was saved.');

                return self::FAILURE;
            }
        } else {
            $mealOnly = $this->gatherMealOnlyTruth();
            $meal = [...$meal, ...$mealOnly];
            $this->warnOnAtwaterDeviation('Meal totals', new NutrientValues(
                calories: $mealOnly['total_kcal'],
                protein: $mealOnly['total_protein_g'],
                carbs: $mealOnly['total_carbs_g'],
                fat: $mealOnly['total_fat_g'],
            ));
        }

        $meal['notes'] = text(label: 'Notes (hidden-ingredient context, conversions, anything odd)') ?: null;

        $stored = $this->persist($meal, $items, $photoPath, $extension);
        $this->summarize($stored);

        return self::SUCCESS;
    }

    /**
     * @return array{tranche: int|string, collected_on: string, cuisine: string, dish_type: int|string, lighting: int|string, angle: int|string, truth_scope: int|string, total_weight_g: float}
     */
    private function gatherMeal(): array
    {
        return [
            'tranche' => select(label: 'Tranche', options: $this->enumOptions(Tranche::cases()), default: Tranche::Hand->value),
            'collected_on' => text(
                label: 'Collected on',
                default: now()->toDateString(),
                validate: fn (string $value): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? null : 'Use an ISO date like 2026-06-14.',
            ),
            'cuisine' => mb_strtolower(text(label: 'Cuisine (lowercase tag, e.g. mongolian, western)', required: true)),
            'dish_type' => select(label: 'Dish type', options: $this->enumOptions(DishType::cases())),
            'lighting' => select(label: 'Lighting', options: $this->enumOptions(Lighting::cases())),
            'angle' => select(label: 'Camera angle', options: $this->enumOptions(CameraAngle::cases())),
            'truth_scope' => select(label: 'Truth scope', options: $this->enumOptions(TruthScope::cases()), default: TruthScope::PerItem->value),
            'total_weight_g' => $this->floatPrompt('Total meal weight (g)'),
        ];
    }

    /**
     * @return list<GatheredItem>
     */
    private function gatherItems(): array
    {
        $items = [];

        do {
            $this->line(sprintf('Item %d', count($items) + 1));

            $item = [
                'name' => text(label: 'Item name (plain English, prepared state, e.g. "rice, white, cooked")', required: true),
                'visible' => confirm(label: 'Visible in the photo?', default: true),
                'weight_g' => $this->floatPrompt('Weight as served (g)'),
                'kcal_per_100g' => $this->floatPrompt('kcal per 100g', allowZero: true),
                'carbs_per_100g' => $this->floatPrompt('Carbs per 100g', allowZero: true),
                'protein_per_100g' => $this->floatPrompt('Protein per 100g', allowZero: true),
                'fat_per_100g' => $this->floatPrompt('Fat per 100g', allowZero: true),
                'truth_source' => select(label: 'Truth source', options: $this->enumOptions(TruthSource::cases()), default: TruthSource::Reference->value),
                'truth_ref' => text(label: 'Truth reference (FDC id, product name, or recipe id)') ?: null,
            ];

            $this->warnOnAtwaterDeviation($item['name'], new NutrientValues(
                calories: $item['kcal_per_100g'],
                protein: $item['protein_per_100g'],
                carbs: $item['carbs_per_100g'],
                fat: $item['fat_per_100g'],
            ));

            $items[] = $item;
        } while (confirm(label: 'Add another item?', default: true));

        return $items;
    }

    /**
     * @return array{total_kcal: float, total_carbs_g: float, total_protein_g: float, total_fat_g: float, truth_source: int|string, truth_ref: string}
     */
    private function gatherMealOnlyTruth(): array
    {
        return [
            'total_kcal' => $this->floatPrompt('Total kcal', allowZero: true),
            'total_carbs_g' => $this->floatPrompt('Total carbs (g)', allowZero: true),
            'total_protein_g' => $this->floatPrompt('Total protein (g)', allowZero: true),
            'total_fat_g' => $this->floatPrompt('Total fat (g)', allowZero: true),
            'truth_source' => select(label: 'Truth source', options: $this->enumOptions(TruthSource::cases()), default: TruthSource::Label->value),
            'truth_ref' => text(label: 'Truth reference (product name as labelled)', required: true),
        ];
    }

    /**
     * @param  list<GatheredItem>  $items
     */
    private function confirmItemWeights(array $items, float $totalWeight): bool
    {
        $sum = array_sum(array_map(fn (array $item): float => $item['weight_g'], $items));
        $deviation = abs($sum - $totalWeight) / $totalWeight;

        if ($deviation <= self::WEIGHT_SUM_TOLERANCE) {
            return true;
        }

        $this->warn(sprintf(
            'Item weights sum to %.1f g but the meal total is %.1f g (%.1f%% off). A gap beyond 5%% usually means a weighing error or an unrecorded component.',
            $sum,
            $totalWeight,
            $deviation * 100,
        ));

        return confirm(label: 'Record the meal anyway?', default: false);
    }

    private function warnOnAtwaterDeviation(string $subject, NutrientValues $values): void
    {
        $deviation = $values->atwaterDeviationRatio();

        if ($deviation !== null && $deviation > self::ATWATER_TOLERANCE) {
            $this->warn(sprintf(
                '%s: kcal deviates %.0f%% from the 4-4-9 Atwater estimate — double-check the values (fiber and label rounding explain up to ~25%%).',
                $subject,
                $deviation * 100,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $meal
     * @param  list<array<string, mixed>>  $items
     */
    private function persist(array $meal, array $items, string $photoPath, string $extension): BenchmarkMeal
    {
        $code = BenchmarkMeal::nextCode();
        $disk = config()->string('plate.benchmark.photo_disk');

        $storedPath = Storage::disk($disk)->putFileAs(
            BenchmarkMeal::PHOTO_DIRECTORY,
            new PhotoFile($photoPath),
            sprintf('%s.%s', $code, $extension),
        );

        return DB::transaction(function () use ($meal, $items, $code, $disk, $storedPath): BenchmarkMeal {
            $stored = BenchmarkMeal::query()->create([
                ...$meal,
                'code' => $code,
                'photo_disk' => $disk,
                'photo_path' => $storedPath,
            ]);

            foreach ($items as $index => $item) {
                $stored->items()->create([...$item, 'position' => $index + 1]);
            }

            return $stored;
        });
    }

    private function summarize(BenchmarkMeal $meal): void
    {
        $items = $meal->items()->get();

        if ($items->isNotEmpty()) {
            $this->table(
                ['#', 'Item', 'Visible', 'Weight (g)', 'kcal', 'Carbs (g)', 'Protein (g)', 'Fat (g)'],
                $items->map(function (BenchmarkMealItem $item): array {
                    $served = $item->asServed();

                    return [$item->position, $item->name, $item->visible ? 'yes' : 'no', $item->weight_g, $served->calories, $served->carbs, $served->protein, $served->fat];
                })->all(),
            );
        }

        $totals = $meal->truthTotals();

        $this->info(sprintf(
            'Recorded %s — truth: %.1f kcal, %.1f g carbs, %.1f g protein, %.1f g fat. Photo stored on "%s" at %s.',
            $meal->code,
            $totals->calories,
            $totals->carbs,
            $totals->protein,
            $totals->fat,
            $meal->photo_disk,
            $meal->photo_path,
        ));
    }

    /**
     * @param  list<BackedEnum>  $cases
     * @return list<string>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(fn (BackedEnum $case): string => (string) $case->value, $cases);
    }

    private function floatPrompt(string $label, bool $allowZero = false): float
    {
        return (float) text(
            label: $label,
            required: true,
            validate: function (string $value) use ($allowZero): ?string {
                if (! is_numeric($value)) {
                    return 'Enter a number.';
                }

                if (! $allowZero && (float) $value <= 0.0) {
                    return 'Enter a number greater than zero.';
                }

                return (float) $value < 0.0 ? 'Enter a non-negative number.' : null;
            },
        );
    }
}
