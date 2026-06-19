<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\MealEvaluation;
use App\Data\Benchmark\PathMetrics;
use App\Data\Benchmark\PredictedRun;
use App\Data\FoodAnalysisData;
use App\Data\FoodItemData;
use App\Data\NutrientValues;
use App\Enums\Benchmark\AnalysisPath;
use App\Models\BenchmarkMeal;
use App\Models\BenchmarkMealItem;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\DataCollection;
use Throwable;

final readonly class BenchmarkHarness
{
    public function __construct(
        private FoodPhotoAnalyzerAgent $agent,
        private AnalyzeFoodPhotoAction $action,
        private ItemMatcher $itemMatcher,
        private MetricsCalculator $calculator,
    ) {}

    /**
     * @param  Collection<int, BenchmarkMeal>  $meals
     */
    public function run(Collection $meals, int $repeats, ?Closure $onAnalysis = null): HarnessReport
    {
        $evaluations = array_fill_keys(array_column(AnalysisPath::cases(), 'value'), []);
        $failures = array_fill_keys(array_column(AnalysisPath::cases(), 'value'), 0);
        $skippedMeals = 0;

        foreach ($meals as $meal) {
            $photo = $this->loadPhoto($meal);

            if ($photo === null) {
                // @codeCoverageIgnoreStart
                $skippedMeals++;

                continue;
                // @codeCoverageIgnoreEnd
            }

            [$imageBase64, $mimeType] = $photo;
            $truth = $meal->truthTotals();
            $truthNames = array_values($meal->items
                ->filter(fn (BenchmarkMealItem $item): bool => $item->visible)
                ->map(fn (BenchmarkMealItem $item): string => $item->name)
                ->all());

            foreach (AnalysisPath::cases() as $path) {
                $runs = [];

                for ($attempt = 0; $attempt < $repeats; $attempt++) {
                    try {
                        $runs[] = $this->toPredictedRun($this->analyze($path, $imageBase64, $mimeType), $truthNames);
                        // @codeCoverageIgnoreStart
                    } catch (Throwable) {
                        $failures[$path->value]++;
                        // @codeCoverageIgnoreEnd
                    }

                    if ($onAnalysis instanceof Closure) {
                        $onAnalysis();
                    }
                }

                $evaluations[$path->value][] = new MealEvaluation(
                    mealCode: $meal->code,
                    mealWeightG: $meal->total_weight_g,
                    truth: $truth,
                    runs: new DataCollection(PredictedRun::class, $runs),
                );
            }
        }

        $paths = array_map(
            fn (AnalysisPath $path): PathMetrics => new PathMetrics(
                path: $path,
                failedRuns: $failures[$path->value],
                metrics: $this->calculator->calculate($evaluations[$path->value]),
            ),
            AnalysisPath::cases(),
        );

        return new HarnessReport(
            analyzerVersion: FoodPhotoAnalyzerAgent::version(),
            referenceLookupEnabled: config()->boolean('plate.food_photo_analyzer.reference_lookup.enabled', false),
            repeats: $repeats,
            skippedMeals: $skippedMeals,
            paths: new DataCollection(PathMetrics::class, $paths),
        );
    }

    private function analyze(AnalysisPath $path, string $imageBase64, string $mimeType): FoodAnalysisData
    {
        return $path === AnalysisPath::Raw
            ? $this->agent->analyze($imageBase64, $mimeType)
            : $this->action->handle($imageBase64, $mimeType);
    }

    /**
     * @param  list<string>  $truthNames
     */
    private function toPredictedRun(FoodAnalysisData $analysis, array $truthNames): PredictedRun
    {
        $predictedNames = [];

        /** @var FoodItemData $item */
        foreach ($analysis->items as $item) {
            $predictedNames[] = $item->matchName ?? $item->name;
        }

        $score = $this->itemMatcher->score($predictedNames, $truthNames);

        return new PredictedRun(
            values: new NutrientValues(
                calories: $analysis->totalCalories,
                protein: $analysis->totalProtein,
                carbs: $analysis->totalCarbs,
                fat: $analysis->totalFat,
            ),
            confidence: $analysis->confidence,
            itemRecall: $score->recall,
            itemPrecision: $score->precision,
        );
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function loadPhoto(BenchmarkMeal $meal): ?array
    {
        try {
            $contents = Storage::disk($meal->photo_disk)->get($meal->photo_path);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return null;
            // @codeCoverageIgnoreEnd
        }

        if ($contents === null) {
            return null; // @codeCoverageIgnore
        }

        $extension = mb_strtolower(pathinfo($meal->photo_path, PATHINFO_EXTENSION));

        return [base64_encode($contents), $extension === 'png' ? 'image/png' : 'image/jpeg'];
    }
}
