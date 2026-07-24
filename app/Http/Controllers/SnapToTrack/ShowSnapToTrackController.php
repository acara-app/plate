<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowSnapToTrackController
{
    public function __invoke(): Response
    {
        $authPath = session()->pull('snap_to_track.auth_path');

        if (is_string($authPath)) {
            Inertia::flash('analytics', [
                'name' => 'snap_to_track_auth_completed',
                'properties' => ['auth_path' => $authPath],
            ]);
        }

        return Inertia::render('snap-to-track/index', [
            'savedGroupId' => session('snap_to_track_saved_group'),
            'creditLimit' => session('snap_to_track_credit_limit'),
        ]);
    }
}
