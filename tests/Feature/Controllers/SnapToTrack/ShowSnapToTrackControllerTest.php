<?php

declare(strict_types=1);

use App\Http\Controllers\SnapToTrack\ShowSnapToTrackController;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

covers(ShowSnapToTrackController::class);

beforeEach(function (): void {
    $this->withoutVite();

    $this->user = User::factory()->create();
});

it('renders the snap to track module', function (): void {
    actingAs($this->user)
        ->get(route('snap-to-track.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('snap-to-track/index')
            ->where('savedGroupId', null));
});

it('exposes the saved entry group after a meal was logged', function (): void {
    actingAs($this->user)
        ->withSession(['snap_to_track_saved_group' => 'group-uuid'])
        ->get(route('snap-to-track.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('savedGroupId', 'group-uuid'));
});

it('exposes the credit limit panel data after a blocked scan', function (): void {
    actingAs($this->user)
        ->withSession(['snap_to_track_credit_limit' => ['tier' => 'free', 'limit_credits' => 400, 'current_credits' => 401, 'resets_in' => '3 hours 10 minutes']])
        ->get(route('snap-to-track.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('creditLimit.tier', 'free')
            ->where('creditLimit.limit_credits', 400)
            ->where('creditLimit.current_credits', 401));
});

it('completes the auth funnel when a limit-recovery signup lands on the module', function (): void {
    $response = actingAs($this->user)
        ->withSession(['snap_to_track.auth_path' => 'register'])
        ->get(route('snap-to-track.index'));

    $response->assertSessionMissing('snap_to_track.auth_path');

    expect(data_get($response->inertiaPage(), 'flash.analytics'))->toBe([
        'name' => 'snap_to_track_auth_completed',
        'properties' => ['auth_path' => 'register'],
    ]);
});
