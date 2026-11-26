<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Models\AnalysisDraft;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\Helpers\FakeGuestCheckout;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

function openGuestResultWithScansUsed(int $used): Pest\Browser\Api\AwaitableWebpage
{
    $starter = sellPhotoPlans();
    $allowance = test()->mock(ManagesPhotoAnalyses::class);
    $allowance->shouldReceive('enabled')->andReturn(true);
    $allowance->shouldReceive('entitlement')->andReturn(new PhotoEntitlement(
        enabled: true, limit: 2, used: $used, mode: 'trial', canUpgrade: true,
        offer: new PhotoOffer($starter->id, $starter->name, $starter->formatted_price),
    ));

    $token = Str::random(64);
    AnalysisDraft::factory()->create(['token_hash' => AnalysisDraft::hashToken($token)]);
    test()->withSession([
        'snap_to_track.result' => ['draft' => $token, 'request_id' => (string) Str::uuid()],
        'snap_to_track.analysis_guest_id' => (string) Str::uuid(),
    ]);
    session()->save();
    test()->withCookie(config()->string('session.cookie'), session()->getId());

    return visit(route('snap-to-track.result', absolute: false))->resize(390, 844);
}

it('starts checkout only when the guest confirms Starter in the first-scan sheet', function (): void {
    $checkout = FakeGuestCheckout::install();
    $checkout->failNextWith = 409;

    $free = 'input[name="snap-result-plan"][value="free"]';
    $paid = 'input[name="snap-result-plan"][value="paid"]';
    $paidButton = 'button:has-text("Continue to checkout")';
    $page = openGuestResultWithScansUsed(1);
    $page->script('window.snapEvents = []; window.umami = { track: (name, properties) => window.snapEvents.push({ name, ...properties }) };');
    $sheetEvents = "window.snapEvents.filter(event => event.placement === 'result_bar').map(event => [event.name, event.free_scans_used])";

    $page->assertMissing('[role="dialog"]')
        ->assertScript($sheetEvents, [])
        ->wait(8)
        ->assertMissing('[role="dialog"]')
        ->wait(2)
        ->assertSee('Keep this meal in your food log')
        ->assertChecked($free)
        ->assertVisible('Save this meal free')
        ->assertSeeIn('a:has-text("Scan another meal")', '1 free scan remaining')
        ->assertMissing($paidButton)
        ->assertScript($sheetEvents, [['snap_to_track_offer_viewed', 1]]);

    $page->keys($free, 'ArrowRight')
        ->assertChecked($paid)
        ->assertMissing('Save this meal free')
        ->assertAttribute('a:has-text("Plan details")', 'href', route('checkout.subscription'));
    expect($checkout->sessions)->toBeEmpty();

    $page->click($paidButton)
        ->assertSeeIn('[role="dialog"] [role="alert"]', 'We could not open checkout just now. Please try again.')
        ->assertScript($sheetEvents, [['snap_to_track_offer_viewed', 1], ['snap_to_track_upgrade_click', 1]]);
    expect($checkout->sessions)->toHaveCount(1);

    $page->keys($paid, 'ArrowLeft')
        ->click('Scan another meal')
        ->assertPathIs(route('snap-to-track', absolute: false))
        ->assertNoJavaScriptErrors();
});

it('shows one Starter impression with two scans used when the exhausted guest sees the sheet', function (): void {
    $page = openGuestResultWithScansUsed(2);
    $page->script('window.sheetViews = []; window.umami = { track: (name, data) => { if (name === "snap_to_track_offer_viewed" && data.placement === "result_bar") window.sheetViews.push(data) } };');

    $page->assertScript('window.sheetViews', [])
        ->wait(10)
        ->assertVisible('[role="dialog"]')
        ->assertScript('window.sheetViews', [[
            'source' => 'public_snap_to_track',
            'placement' => 'result_bar',
            'variant' => 'baseline',
            'free_scans_used' => 2,
        ]])
        ->assertNoJavaScriptErrors();
});
