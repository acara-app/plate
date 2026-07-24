<?php

declare(strict_types=1);

use App\Http\Controllers\SnapToTrack\ShowSnapToTrackController;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

covers(ShowSnapToTrackController::class);

beforeEach(function (): void {
    $this->withoutVite();

    config()->set('plate.snap_to_track.activation_funnel', true);

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

it('returns not found when the activation funnel is disabled', function (): void {
    config()->set('plate.snap_to_track.activation_funnel', false);

    actingAs($this->user)
        ->get(route('snap-to-track.index'))
        ->assertNotFound();
});
