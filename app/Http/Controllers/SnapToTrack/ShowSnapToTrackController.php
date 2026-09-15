<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\Billing\BuildSnapBurstLimit;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Actions\Billing\ResolveSnapBurstCap;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\ModelName;
use App\Exceptions\Billing\UsageLimitExceededException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowSnapToTrackController
{
    public function __invoke(
        Request $request,
        EnforceAiUsageLimit $enforceAiUsageLimit,
        ResolveSnapBurstCap $resolveSnapBurstCap,
        BuildSnapBurstLimit $buildSnapBurstLimit,
    ): Response {
        $authPath = session()->pull('snap_to_track.auth_path');

        if (is_string($authPath)) {
            Inertia::flash('analytics', [
                'name' => 'snap_to_track_auth_completed',
                'properties' => ['auth_path' => $authPath],
            ]);
        }

        $allowance = resolve(ManagesPhotoAnalyses::class)->entitlement($request->user(), PhotoAnalysisContext::guestId($request));
        $request->session()->forget('snap_to_track_credit_limit');
        $creditLimit = null;
        if (! $allowance->enabled && $request->user() !== null) {
            try {
                $enforceAiUsageLimit->handle($request->user(), ModelName::tryFrom(config()->string('plate.food_photo_analyzer.model')));
            } catch (UsageLimitExceededException $exception) {
                $creditLimit = $exception->toPayload();
            }
        }

        $burstKey = ResolveSnapBurstCap::cacheKeyFor($request->user(), $request->ip());
        $burstLimit = RateLimiter::tooManyAttempts($burstKey, $resolveSnapBurstCap->handle($request->user()))
            ? $buildSnapBurstLimit->handle($request, RateLimiter::availableIn($burstKey))
            : null;

        return Inertia::render('snap-to-track/index', [
            'photoAllowance' => $allowance->toArray(),
            'analysisRequestId' => (string) Str::uuid(),
            'savedGroupId' => session('snap_to_track_saved_group'),
            'creditLimit' => $creditLimit,
            'burstLimit' => $burstLimit?->toArray(),
        ]);
    }
}
