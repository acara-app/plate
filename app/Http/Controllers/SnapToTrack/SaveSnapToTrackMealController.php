<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\LogAnalyzedMeal;
use App\Actions\RestoreAnalysisDraftAction;
use App\Data\AnalysisDraftResolutionData;
use App\Enums\AnalysisDraftSource;
use App\Enums\AnalysisDraftStatus;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class SaveSnapToTrackMealController
{
    public function __construct(
        private RestoreAnalysisDraftAction $restoreAnalysisDraft,
        private LogAnalyzedMeal $logAnalyzedMeal,
    ) {}

    public function __invoke(#[CurrentUser] User $user, string $draft): RedirectResponse
    {
        $resolution = $this->restoreAnalysisDraft->handle($draft, $user);
        $draftModel = $resolution->draft;

        if (! $draftModel instanceof AnalysisDraft || ! in_array($resolution->status, [AnalysisDraftStatus::Restored, AnalysisDraftStatus::Consumed], true)) {
            return $this->unavailable($resolution);
        }

        $meal = $this->logAnalyzedMeal->handle($draftModel, $user);

        if ($resolution->status === AnalysisDraftStatus::Restored) {
            $this->flashSaved($draftModel, $draft);
        }

        return to_route('health-entries.show', $meal);
    }

    private function flashSaved(AnalysisDraft $draftModel, string $draft): void
    {
        $events = [];
        $authPath = session()->pull('snap_to_track.auth_path');

        if (is_string($authPath)) {
            $events[] = ['name' => 'snap_to_track_auth_completed', 'properties' => ['auth_path' => $authPath]];
        }

        $events[] = ['name' => 'snap_to_track_draft_restored', 'properties' => ['draft_age_band' => $draftModel->ageBand()]];
        $events[] = ['name' => 'snap_to_track_meal_logged', 'properties' => [
            'source' => $draftModel->source->value,
            'items_count' => count(is_array($draftModel->payload['items'] ?? null) ? $draftModel->payload['items'] : []),
        ]];

        Inertia::flash('analytics', $events);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('common.snap_to_track.saved')]);

        $requestId = session('snap_to_track.result.request_id');
        $guestDraft = session('snap_to_track.result.draft');

        if ($draftModel->source === AnalysisDraftSource::PublicSnapToTrack && is_string($requestId) && is_string($guestDraft) && hash_equals($guestDraft, $draft)) {
            Inertia::flash('snap_to_track_photo', [
                'url' => route('snap-to-track.save.photo', ['draft' => $draft], absolute: false),
                'request_id' => $requestId,
            ]);
        }
    }

    private function unavailable(AnalysisDraftResolutionData $resolution): RedirectResponse
    {
        session()->forget('snap_to_track.auth_path');
        $expired = $resolution->status === AnalysisDraftStatus::Expired;

        if ($expired && $resolution->draft instanceof AnalysisDraft) {
            Inertia::flash('analytics', [
                'name' => 'snap_to_track_draft_expired',
                'properties' => ['draft_age_band' => $resolution->draft->ageBand()],
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __($expired ? 'common.snap_to_track.unavailable.expired' : 'common.snap_to_track.unavailable.generic'),
        ]);

        return to_route('snap-to-track.index');
    }
}
