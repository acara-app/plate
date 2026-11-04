<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Billing\PhotoOffer;

final readonly class PrepareSnapToTrackUpgradeAction
{
    public function handle(?PhotoOffer $offer, ?string $draftToken = null): string
    {
        session()->put('snap_to_track.intent', 'upgrade');

        if ($draftToken !== null) {
            session()->put('snap_to_track.upgrade_draft', $draftToken);
        }

        session()->put('url.intended', route('checkout.subscription', absolute: false));

        return $offer instanceof PhotoOffer
            ? route('checkout.start', ['product' => $offer->productId])
            : route('checkout.subscription');
    }
}
