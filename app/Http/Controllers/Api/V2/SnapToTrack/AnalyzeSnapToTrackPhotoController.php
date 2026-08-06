<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\SnapToTrack;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Actions\CreateAnalysisDraftAction;
use App\Enums\AnalysisDraftSource;
use App\Enums\ModelName;
use App\Http\Requests\Api\V2\SnapToTrack\AnalyzeSnapToTrackPhotoRequest;
use App\Models\AnalysisDraft;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Throwable;

final readonly class AnalyzeSnapToTrackPhotoController
{
    public function __construct(
        private AnalyzeFoodPhotoAction $analyzeFoodPhoto,
        private CreateAnalysisDraftAction $createAnalysisDraft,
        private EnforceAiUsageLimit $enforceAiUsageLimit,
    ) {}

    public function __invoke(AnalyzeSnapToTrackPhotoRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->enforceAiUsageLimit->handle(
            $user,
            ModelName::tryFrom(config()->string('plate.food_photo_analyzer.model')),
        );

        $image = $request->image();

        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve(
            $this->requestedLocale($request) ?? $user->locale,
        );

        try {
            $analysis = $this->analyzeFoodPhoto->handle(
                $image->base64(),
                $image->mimeType,
                $language,
                $languageCode,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json(['error' => 'analysis_failed'], 500);
        }

        $token = $this->createAnalysisDraft->handle(
            $analysis,
            AnalysisDraftSource::MobileSnapToTrack,
            $user->id,
        );

        $draft = AnalysisDraft::query()
            ->where('token_hash', AnalysisDraft::hashToken($token))
            ->first();

        return response()->json([
            'draft_token' => $token,
            'expires_at' => $draft?->expires_at->toIso8601String(),
            'analysis' => $analysis->toArray(),
        ]);
    }

    private function requestedLocale(AnalyzeSnapToTrackPhotoRequest $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $code = mb_strtolower(explode('_', str_replace('-', '_', $language))[0]);

            if (LanguageUtil::has($code)) {
                return $code;
            }
        }

        return null;
    }
}
