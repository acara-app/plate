<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Models\PlanPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class OpenPlanPurchaseClaimLinkController
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $purchase = PlanPurchase::findByClaimToken($token);

        $request->session()->put('snap_to_track.claim', [
            'purchase' => $purchase?->id,
            'token_hash' => PlanPurchase::hashToken($token),
        ]);

        return to_route('snap-to-track.activate');
    }
}
