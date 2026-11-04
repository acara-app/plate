<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Billing\PhotoOffer;
use Illuminate\Contracts\Session\Session;

final readonly class PrepareSnapToTrackUpgrade
{
    public function __construct(private Session $session) {}

    public function handle(?PhotoOffer $offer, ?string $draftToken = null): string
    {
        $this->session->put('snap_to_track.intent', 'upgrade');

        if ($draftToken !== null) {
            $this->session->put('snap_to_track.upgrade_draft', $draftToken);
        }

        $this->session->put('url.intended', route('checkout.subscription', absolute: false));

        return $offer instanceof PhotoOffer
            ? route('checkout.start', ['product' => $offer->productId])
            : route('checkout.subscription');
    }
}
