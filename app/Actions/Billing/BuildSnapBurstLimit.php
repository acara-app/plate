<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\BurstLimit;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\SubscriptionTier;
use App\Models\User;
use Illuminate\Http\Request;

final readonly class BuildSnapBurstLimit
{
    public function __construct(
        private ResolveSnapBurstCap $burstCap,
        private ResolvesUserTier $tiers,
        private ManagesPhotoAnalyses $photoAnalyses,
    ) {}

    public function handle(Request $request, int $retryAfterSeconds): BurstLimit
    {
        $user = $request->user();

        return BurstLimit::forTier(
            tier: $user instanceof User ? $this->tiers->resolve($user)->tier : SubscriptionTier::Free,
            cap: $this->burstCap->handle($user),
            retryAfterSeconds: $retryAfterSeconds,
            offer: $this->photoAnalyses->entitlement($user, PhotoAnalysisContext::guestId($request))->offer,
        );
    }
}
