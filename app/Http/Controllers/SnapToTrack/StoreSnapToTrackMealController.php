<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\LogReviewedMealAction;
use App\Data\ReviewedMealData;
use App\Http\Requests\SnapToTrack\StoreSnapToTrackMealRequest;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class StoreSnapToTrackMealController
{
    public function __construct(
        private LogReviewedMealAction $logReviewedMeal,
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

        $meal = ReviewedMealData::fromValidated($request->validated());

        $groupId = $this->logReviewedMeal->handle(
            $draftModel,
            $meal->toHealthLogData($draftModel),
            $this->currentUser,
        );

        Inertia::flash('analytics', [
            'name' => 'snap_to_track_meal_logged',
            'properties' => [
                'source' => $draftModel->source->value,
                'items_count' => count($meal->items),
            ],
        ]);

        return to_route('snap-to-track.index')
            ->with('snap_to_track_saved_group', $groupId);
    }
}
