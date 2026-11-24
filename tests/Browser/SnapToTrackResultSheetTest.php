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

it('starts checkout only when the guest confirms Starter in the first-scan sheet', function (): void {
    $starter = sellPhotoPlans();
    $checkout = FakeGuestCheckout::install();
    $checkout->failNextWith = 409;

    $allowance = $this->mock(ManagesPhotoAnalyses::class);
    $allowance->shouldReceive('enabled')->andReturn(true);
    $allowance->shouldReceive('entitlement')->andReturn(new PhotoEntitlement(
        enabled: true, limit: 2, used: 1, mode: 'trial', canUpgrade: true,
        offer: new PhotoOffer($starter->id, $starter->name, $starter->formatted_price),
    ));

    $token = Str::random(64);
    AnalysisDraft::factory()->create(['token_hash' => AnalysisDraft::hashToken($token)]);
    $this->withSession([
        'snap_to_track.result' => ['draft' => $token, 'request_id' => (string) Str::uuid()],
        'snap_to_track.analysis_guest_id' => (string) Str::uuid(),
    ]);
    session()->save();
    $this->withCookie(config()->string('session.cookie'), session()->getId());

    $free = 'input[name="snap-result-plan"][value="free"]';
    $paid = 'input[name="snap-result-plan"][value="paid"]';
    $paidButton = 'button:has-text("Get Starter")';
    $page = visit(route('snap-to-track.result', absolute: false))->resize(390, 844);
    $page->assertChecked($free)
        ->assertVisible('Save this meal free')
        ->assertMissing($paidButton)
        ->keys($free, 'ArrowRight')
        ->assertChecked($paid)
        ->assertMissing('Save this meal free')
        ->assertVisible($paidButton);
    expect($checkout->sessions)->toBeEmpty();

    $page->click($paidButton)
        ->assertSee('We could not open checkout just now. Please try again.')
        ->assertNoJavaScriptErrors();
    expect($checkout->sessions)->toHaveCount(1);
});
