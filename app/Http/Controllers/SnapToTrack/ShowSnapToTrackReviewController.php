<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\RestoreAnalysisDraftAction;
use App\Data\AnalysisDraftResolutionData;
use App\Enums\AnalysisDraftStatus;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowSnapToTrackReviewController
{
    public function __construct(
        private RestoreAnalysisDraftAction $restoreAnalysisDraft,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(string $draft): Response
    {
        $resolution = $this->restoreAnalysisDraft->handle($draft, $this->currentUser);

        if ($resolution->status !== AnalysisDraftStatus::Restored) {
            return $this->renderUnavailable($resolution);
        }

        $this->flashRestoredAnalytics($resolution);

        return Inertia::render('snap-to-track/review', [
            'state' => 'restored',
            'reason' => null,
            'analysis' => $resolution->analysis(),
            'draftToken' => $draft,
            'source' => $resolution->draft?->source->value,
        ]);
    }

    private function renderUnavailable(AnalysisDraftResolutionData $resolution): Response
    {
        session()->forget('snap_to_track.auth_path');

        if ($resolution->status === AnalysisDraftStatus::Expired && $resolution->draft !== null) {
            Inertia::flash('analytics', [
                'name' => 'snap_to_track_draft_expired',
                'properties' => ['draft_age_band' => $resolution->draft->ageBand()],
            ]);
        }

        return Inertia::render('snap-to-track/review', [
            'state' => 'unavailable',
            'reason' => $resolution->status->value,
            'analysis' => null,
            'draftToken' => null,
            'source' => null,
        ]);
    }

    private function flashRestoredAnalytics(AnalysisDraftResolutionData $resolution): void
    {
        $events = [];

        $authPath = session()->pull('snap_to_track.auth_path');

        if (is_string($authPath)) {
            $events[] = [
                'name' => 'snap_to_track_auth_completed',
                'properties' => ['auth_path' => $authPath],
            ];
        }

        $events[] = [
            'name' => 'snap_to_track_draft_restored',
            'properties' => ['draft_age_band' => $resolution->draft?->ageBand()],
        ];

        Inertia::flash('analytics', count($events) === 1 ? $events[0] : $events);
    }
}
