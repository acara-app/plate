<?php

declare(strict_types=1);

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\Telemetry\PaywallEvent;
use App\Http\Controllers\Telemetry\RecordPaywallEventController;
use App\Models\User;
use Tests\Helpers\FakePaywallTelemetry;

covers(RecordPaywallEventController::class);

beforeEach(function (): void {
    $this->fake = new FakePaywallTelemetry();
    $this->app->instance(EmitsPaywallEvents::class, $this->fake);
});

it('records paywall_shown from the client and forwards it to the telemetry pipeline', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('telemetry.paywall.record'), [
        'event' => 'paywall_shown',
        'payload' => [
            'trigger' => 'feature',
            'feature' => 'meal_planner',
            'tier_target' => 'basic',
        ],
    ]);

    $response->assertStatus(202)->assertJson(['accepted' => true]);

    $events = $this->fake->eventsOfType(PaywallEvent::PaywallShown);
    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'trigger' => 'feature',
            'feature' => 'meal_planner',
            'tier_target' => 'basic',
            'surface' => 'client',
        ]);
});

it('records upgrade_clicked with the supplied surface', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('telemetry.paywall.record'), [
        'event' => 'upgrade_clicked',
        'payload' => [
            'trigger' => 'cap',
            'limit_type' => 'rolling',
            'tier_target' => 'plus',
            'surface' => 'paywall_modal',
        ],
    ]);

    $response->assertStatus(202);

    $events = $this->fake->eventsOfType(PaywallEvent::UpgradeClicked);
    expect($events)->toHaveCount(1)
        ->and($events[0]['payload'])->toMatchArray([
            'trigger' => 'cap',
            'limit_type' => 'rolling',
            'tier_target' => 'plus',
            'surface' => 'paywall_modal',
        ]);
});

it('rejects unknown client event names with 422', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('telemetry.paywall.record'), [
        'event' => 'checkout_completed',
        'payload' => [],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('event');
    expect($this->fake->emitted)->toBeEmpty();
});

it('rejects unauthenticated requests', function (): void {
    $response = $this->postJson(route('telemetry.paywall.record'), [
        'event' => 'paywall_shown',
    ]);

    $response->assertStatus(401);

    expect($this->fake->emitted)->toBeEmpty();
});

it('rejects requests with no event field', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('telemetry.paywall.record'), [
        'payload' => [],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('event');
});
