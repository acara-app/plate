<?php

declare(strict_types=1);

use App\Enums\SnapFunnelStage;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapJourney;
use App\Models\User;
use App\Services\Billing\SnapFunnel;
use Illuminate\Support\Str;

it('keeps community installations free of commercial attribution', function (): void {
    $user = User::factory()->create();
    $funnel = resolve(SnapFunnel::class);
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());

    $funnel->record(SnapFunnelStage::UploadSelected, 'community-upload');

    expect($funnel->checkoutMetadata($user))->toBe([])
        ->and(SnapJourney::query()->count())->toBe(0)
        ->and(SnapAnalyticsEvent::query()->count())->toBe(0);
});

it('explains the Snap destination on signup without losing the selected plan', function (): void {
    $this->withSession([
        'snap_to_track.intent' => 'upgrade',
        'checkout.selected_product' => 7,
        'url.intended' => '/checkout/subscription',
    ])->get(route('register'))->assertInertia(fn ($page) => $page
        ->where('snapIntent', 'upgrade'))
        ->assertSessionHas('checkout.selected_product', 7)
        ->assertSessionHas('url.intended', '/checkout/subscription');
});
