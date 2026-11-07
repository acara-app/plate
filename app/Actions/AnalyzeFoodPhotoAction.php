<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Data\FoodItemData;
use App\Enums\FoodValueProvenance;
use App\Enums\PhotoAnalysisStatus;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;
use App\Services\Nutrition\ReferenceFoodMatcher;
use App\Services\Nutrition\ReferenceMatch;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\LaravelData\DataCollection;
use Throwable;

final readonly class AnalyzeFoodPhotoAction
{
    public function __construct(
        private FoodPhotoAnalyzerAgent $agent,
        private ReferenceFoodMatcher $matcher,
    ) {}

    public function handle(string $imageBase64, string $mimeType, ?string $language = null, ?string $languageCode = null, ?PhotoAnalysisContext $context = null, ?int $analysisRequestId = null): FoodAnalysisData
    {
        $context ??= PhotoAnalysisContext::fromRequest(request(), 'photo_tool', $imageBase64);

        return resolve(ManagesPhotoAnalyses::class)->analyze(
            $context,
            function (PhotoModel $model) use ($imageBase64, $mimeType, $language, $languageCode, $context, $analysisRequestId): FoodAnalysisData {
                if ($analysisRequestId !== null) {
                    $request = PhotoAnalysisRequest::query()->metadata()->whereKey($analysisRequestId)
                        ->where('request_id', $context->requestId)->where('fingerprint', $context->fingerprint)->where('source', $context->source)
                        ->where('status', PhotoAnalysisStatus::Processing)->whereFuture('deadline_at')->where('expires_at', '>', now()->addSeconds(PhotoAnalysisRequest::PROCESSING_BUDGET_SECONDS))->first();

                    throw_if(! $request instanceof PhotoAnalysisRequest || ! $request->ownedBy($context->user, $context->guestId), RuntimeException::class, 'This photo analysis is no longer processing.');
                }

                return $this->analyzeUsingModel($imageBase64, $mimeType, $model, $language, $languageCode, $context->user, $context->requestId);
            },
        );
    }

    public function analyzeUsingModel(string $imageBase64, string $mimeType, PhotoModel $model, ?string $language = null, ?string $languageCode = null, ?User $user = null, ?string $requestId = null): FoodAnalysisData
    {
        $agent = $this->agent->usingModel($model, $user);

        if ($language !== null && $languageCode !== null) {
            $agent->withLanguage($language, $languageCode);
        }

        $startedAt = microtime(true);
        try {
            $analysis = $agent->analyze($imageBase64, $mimeType);
        } finally {
            Log::info('food_photo_provider_finished', [
                'request_id' => $requestId,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        }

        if (! $this->lookupEnabled()) {
            return $analysis;
        }

        $startedAt = microtime(true);
        try {
            return $this->enrichWithReferenceData($analysis);
        } finally {
            Log::info('food_photo_enrichment_finished', [
                'request_id' => $requestId,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        }
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
            totalFiber: $analysis->totalFiber,
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
                    fiber: $item->fiber,
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
