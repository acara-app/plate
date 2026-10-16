<?php

declare(strict_types=1);

use App\Models\AnalysisDraft;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Str;

it('keeps local meal times and the ingredient breakdown through saving and editing', function (): void {
    $user = User::factory()->create();
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);
    $this->actingAs($user);

    $page = visit(route('snap-to-track.review', ['draft' => $token], absolute: false))
        ->withTimezone('Asia/Ulaanbaatar')
        ->withLocale('en-US');

    $page->fill('measured_at', '2026-10-16T11:57')
        ->press('Log this meal')
        ->assertSee('Meal logged!')
        ->click('Open your logbook')
        ->assertSee('Grilled chicken breast')
        ->assertSee('1 breast (170g)')
        ->assertSee('Steamed rice')
        ->assertSee('11:57:00 AM')
        ->click('button[aria-label="Edit log entry"]')
        ->assertValue('measured_at', '2026-10-16T11:57')
        ->fill('input[name="items[0][name]"]', 'Chicken with sauce')
        ->fill('input[name="items[0][calories]"]', '400')
        ->fill('measured_at', '2026-10-16T12:15')
        ->press('Update')
        ->assertSee('Chicken with sauce')
        ->assertSee('610 kcal')
        ->assertSee('12:15:00 PM')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'dietaryEnergy',
        'value' => 610,
        'measured_at' => '2026-10-16 04:15:00',
    ]);
});

it('explains the enforced limits before subscribing to a photo plan', function (): void {
    offerAllSubscriptions();
    $user = User::factory()->create(['stripe_id' => null]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter',
        'price' => 2.99,
        'features' => ['Photo scans'],
        'coming_soon' => false,
    ]);
    $this->actingAs($user);

    visit(route('checkout.subscription', absolute: false))
        ->assertSee('Starter')
        ->assertSee('Snap Pro')
        ->assertSee('Up to 30 scan attempts per hour')
        ->assertSee('500 AI credits per rolling 24 hours')
        ->assertSee('1,500 AI credits per rolling 7 days')
        ->assertSee('1,500 AI credits per rolling 24 hours')
        ->assertSee('6,000 AI credits per rolling 7 days')
        ->assertSee('there is no fixed daily scan count')
        ->assertNoJavaScriptErrors();
});
