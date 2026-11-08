<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\User;
use App\Utilities\Base64Image;
use Illuminate\Support\Facades\Log;

final readonly class AnalyzeFoodPhotoAction
{
    private const int MODEL_MAX_SIDE = 1024;

    public function __construct(
        private FoodPhotoAnalyzerAgent $agent,
        private ResizePhoto $resizePhoto,
    ) {}

    public function handle(string $imageBase64, string $mimeType, ?string $language = null, ?string $languageCode = null, ?PhotoAnalysisContext $context = null): FoodAnalysisData
    {
        $context ??= PhotoAnalysisContext::fromRequest(request(), 'photo_tool', $imageBase64);

        return resolve(ManagesPhotoAnalyses::class)->analyze(
            $context,
            fn (PhotoModel $model): FoodAnalysisData => $this->analyzeUsingModel($imageBase64, $mimeType, $model, $language, $languageCode, $context->user, $context->requestId),
        );
    }

    public function analyzeUsingModel(string $imageBase64, string $mimeType, PhotoModel $model, ?string $language = null, ?string $languageCode = null, ?User $user = null, ?string $requestId = null): FoodAnalysisData
    {
        $agent = $this->agent->usingModel($model, $user);

        if ($language !== null && $languageCode !== null) {
            $agent->withLanguage($language, $languageCode);
        }

        $resized = $this->resizedForModel($imageBase64);

        if ($resized !== null) {
            $imageBase64 = base64_encode($resized);
            $mimeType = 'image/jpeg';
        }

        $startedAt = microtime(true);
        try {
            return $agent->analyze($imageBase64, $mimeType);
        } finally {
            Log::info('food_photo_provider_finished', [
                'request_id' => $requestId,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }

    private function resizedForModel(string $imageBase64): ?string
    {
        $image = Base64Image::decode($imageBase64);

        if (! $image instanceof Base64Image || max($image->width, $image->height) <= self::MODEL_MAX_SIDE) {
            return null;
        }

        return $this->resizePhoto->handle($image->bytes, self::MODEL_MAX_SIDE);
    }
}
