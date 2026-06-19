<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\FoodAnalysisData;
use App\Data\FoodItemData;
use App\Enums\FoodValueProvenance;
use App\Services\Nutrition\ReferenceFoodMatcher;
use App\Services\Nutrition\ReferenceMatch;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Throwable;

final readonly class AnalyzeFoodPhotoAction
{
    public function __construct(
        private FoodPhotoAnalyzerAgent $agent,
        private ReferenceFoodMatcher $matcher,
    ) {}

    public function handle(string $imageBase64, string $mimeType, ?string $language = null, ?string $languageCode = null): FoodAnalysisData
    {
        if ($language !== null && $languageCode !== null) {
            $this->agent->withLanguage($language, $languageCode);
        }

        $analysis = $this->agent->analyze($imageBase64, $mimeType);

        if (! $this->lookupEnabled()) {
            return $analysis;
        }

        return $this->enrichWithReferenceData($analysis);
    }

    private function enrichWithReferenceData(FoodAnalysisData $analysis): FoodAnalysisData
    {
        $items = [];
        $releases = [];

        foreach ($analysis->items as $item) {
            [$enriched, $release] = $this->enrichItem($item);
            $items[] = $enriched;

            if ($release !== null) {
                $releases[$release] = true;
            }
        }

        $referenceRelease = $releases === [] ? null : implode(', ', array_keys($releases));

        $this->logTelemetry($items, $analysis->analyzerVersion, $referenceRelease);

        if ($referenceRelease === null) {
            return $analysis;
        }

        return new FoodAnalysisData(
            items: new DataCollection(FoodItemData::class, $items),
            totalCalories: $this->sum($items, fn (FoodItemData $item): float => $item->calories),
            totalProtein: $this->sum($items, fn (FoodItemData $item): float => $item->protein),
            totalCarbs: $this->sum($items, fn (FoodItemData $item): float => $item->carbs),
            totalFat: $this->sum($items, fn (FoodItemData $item): float => $item->fat),
            confidence: $analysis->confidence,
            analyzerVersion: $analysis->analyzerVersion,
            referenceRelease: $referenceRelease,
        );
    }

    /**
     * @return array{0: FoodItemData, 1: string|null}
     */
    private function enrichItem(FoodItemData $item): array
    {
        try {
            $match = $this->matcher->match($item->matchName ?? $item->name);

            if ($match instanceof ReferenceMatch && $item->grams !== null && $item->grams > 0.0) {
                $macros = $match->food->macrosFor($item->grams);

                return [new FoodItemData(
                    name: $item->name,
                    calories: $macros->calories,
                    protein: $macros->protein,
                    carbs: $macros->carbs,
                    fat: $macros->fat,
                    portion: $item->portion,
                    grams: $item->grams,
                    matchName: $item->matchName,
                    provenance: FoodValueProvenance::Reference,
                ), $match->food->release];
            }

            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            //
        }

        // @codeCoverageIgnoreEnd

        return [$item, null];
    }

    /**
     * @param  list<FoodItemData>  $items
     * @param  callable(FoodItemData): float  $macro
     */
    private function sum(array $items, callable $macro): float
    {
        return round(array_sum(array_map($macro, $items)), 1);
    }

    /**
     * @param  list<FoodItemData>  $items
     */
    private function logTelemetry(array $items, ?string $analyzerVersion, ?string $referenceRelease): void
    {
        $reference = count(array_filter($items, fn (FoodItemData $item): bool => $item->provenance === FoodValueProvenance::Reference));

        Log::info('food_photo_reference_lookup', [
            'items' => count($items),
            'reference' => $reference,
            'model' => count($items) - $reference,
            'reference_release' => $referenceRelease,
            'analyzer_version' => $analyzerVersion,
        ]);
    }

    private function lookupEnabled(): bool
    {
        return config()->boolean('plate.food_photo_analyzer.reference_lookup.enabled', false);
    }
}
