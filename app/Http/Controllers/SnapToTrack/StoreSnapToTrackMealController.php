<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\LogReviewedMealAction;
use App\Actions\StoreMealPhotoAction;
use App\Data\ReviewedMealData;
use App\Enums\AnalysisDraftSource;
use App\Enums\SnapFunnelStage;
use App\Http\Requests\SnapToTrack\StoreSnapToTrackMealRequest;
use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;
use App\Services\Billing\SnapFunnel;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Throwable;

final readonly class StoreSnapToTrackMealController
{
    public function __construct(
        private LogReviewedMealAction $logReviewedMeal,
        private StoreMealPhotoAction $storeMealPhoto,
        private SnapFunnel $funnel,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(StoreSnapToTrackMealRequest $request, string $draft): RedirectResponse
    {
        $draftModel = AnalysisDraft::query()
            ->where('token_hash', AnalysisDraft::hashToken($draft))
            ->where('user_id', $this->currentUser->id)
            ->where('schema_version', AnalysisDraft::SCHEMA_VERSION)
            ->first();

        if (! $draftModel instanceof AnalysisDraft) {
            return to_route('snap-to-track.review', ['draft' => $draft]);
        }

        $this->keepGuestPhoto($request->file('photo'), $draftModel, $draft);

        $meal = ReviewedMealData::fromValidated($request->validated());

        $this->logReviewedMeal->handle(
            $draftModel,
            $meal->toHealthLogData($draftModel),
            $this->currentUser,
        );

        if ($draftModel->source === AnalysisDraftSource::PublicSnapToTrack) {
            $this->funnel->record(SnapFunnelStage::MealSaved, (string) $draftModel->id, ['items' => count($meal->items)]);
        }

        Inertia::flash('analytics', [
            'name' => 'snap_to_track_meal_logged',
            'properties' => [
                'source' => $draftModel->source->value,
                'items_count' => count($meal->items),
            ],
        ]);

        return to_route('health-entries.index', [
            'date' => $meal->measuredAt->setTimezone($this->currentUser->resolveTimezone())->toDateString(),
        ]);
    }

    private function keepGuestPhoto(mixed $photo, AnalysisDraft $draftModel, string $draft): void
    {
        if (! $photo instanceof UploadedFile
            || $draftModel->source !== AnalysisDraftSource::PublicSnapToTrack
            || $draftModel->consumed_at !== null
            || MealPhoto::query()->where('analysis_draft_id', $draftModel->id)->exists()) {
            return;
        }

        try {
            $this->storeMealPhoto->handle($this->currentUser, (string) $photo->get(), $draft);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
