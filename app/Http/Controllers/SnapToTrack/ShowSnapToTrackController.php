<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\Billing\ResolveAiAllowance;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowSnapToTrackController
{
    public function __invoke(Request $request, ResolveAiAllowance $resolveAiAllowance): Response
    {
        $authPath = session()->pull('snap_to_track.auth_path');

        if (is_string($authPath)) {
            Inertia::flash('analytics', [
                'name' => 'snap_to_track_auth_completed',
                'properties' => ['auth_path' => $authPath],
            ]);
        }

        $allowance = resolve(ManagesPhotoAnalyses::class)->entitlement($request->user(), PhotoAnalysisContext::guestId($request));
        $aiAllowance = ! $allowance->enabled && $request->user() !== null
            ? $resolveAiAllowance->handle($request->user())
            : null;

        return Inertia::render('snap-to-track/index', [
            'photoAllowance' => $allowance->toArray(),
            'aiAllowance' => $aiAllowance?->toArray(),
        ]);
    }
}
