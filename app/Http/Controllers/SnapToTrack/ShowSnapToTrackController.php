<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowSnapToTrackController
{
    public function __invoke(): Response
    {
        return Inertia::render('snap-to-track/index', [
            'savedGroupId' => session('snap_to_track_saved_group'),
        ]);
    }
}
