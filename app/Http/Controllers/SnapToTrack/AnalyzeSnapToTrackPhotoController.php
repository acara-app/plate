<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Actions\CreateAnalysisDraftAction;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\AnalysisDraftSource;
use App\Enums\ConfidenceBand;
use App\Enums\ModelName;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Http\Requests\SnapToTrack\AnalyzeSnapToTrackPhotoRequest;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Throwable;

final readonly class AnalyzeSnapToTrackPhotoController
{
    public function __construct(
        private AnalyzeFoodPhotoAction $analyzeFoodPhoto,
        private CreateAnalysisDraftAction $createAnalysisDraft,
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(AnalyzeSnapToTrackPhotoRequest $request): RedirectResponse
    {
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            return back()->withErrors(['photo' => __('Please select a photo to analyze.')]);
        }

        try {
            if (! resolve(ManagesPhotoAnalyses::class)->entitlement($this->currentUser, null)->enabled) {
                $this->enforceAiUsageLimit->handle(
                    $this->currentUser,
                    ModelName::tryFrom(config()->string('plate.food_photo_analyzer.model')),
                );
            }
        } catch (UsageLimitExceededException $usageLimitExceededException) {
            $this->deleteUploadedPhoto($photo);

            Inertia::flash('analytics', [
                'name' => 'snap_to_track_limit_reached',
                'properties' => [
                    'source' => AnalysisDraftSource::AuthenticatedSnapToTrack->value,
                    'gate' => 'credits',
                    'tier' => $usageLimitExceededException->tier->value,
                ],
            ]);

            return to_route('snap-to-track.index')
                ->with('snap_to_track_credit_limit', $usageLimitExceededException->toPayload());
        }

        try {
            ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($this->currentUser->locale);

            $imageBase64 = base64_encode((string) $photo->get());

            $analysis = $this->analyzeFoodPhoto->handle(
                $imageBase64,
                $photo->getMimeType() ?? 'image/jpeg',
                $language,
                $languageCode,
                PhotoAnalysisContext::fromRequest($request, 'authenticated_snap_to_track', $imageBase64, $this->currentUser),
            );
        } catch (PhotoLimitExceeded $exception) {
            return to_route('snap-to-track.index')->withErrors(['photo' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->withErrors(['photo' => __('Something went wrong. Please try again.')]);
        } finally {
            $this->deleteUploadedPhoto($photo);
        }

        $token = $this->createAnalysisDraft->handle(
            $analysis,
            AnalysisDraftSource::AuthenticatedSnapToTrack,
            $this->currentUser->id,
        );

        Inertia::flash('analytics', [
            'name' => 'snap_to_track_result_viewed',
            'properties' => [
                'source' => AnalysisDraftSource::AuthenticatedSnapToTrack->value,
                'items_count' => $analysis->items->count(),
                'confidence_band' => ConfidenceBand::fromScore($analysis->confidence)->value,
            ],
        ]);

        return to_route('snap-to-track.review', ['draft' => $token]);
    }

    private function deleteUploadedPhoto(UploadedFile $photo): void
    {
        $path = $photo->getRealPath();

        if (is_string($path) && file_exists($path)) {
            @unlink($path);
        }
    }
}
