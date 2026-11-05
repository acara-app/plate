<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

covers(HomeController::class);

it('displays the homepage', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertViewHas('featuredFoods');
});

it('passes featured foods to the view', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();

    $featuredFoods = $response->viewData('featuredFoods');

    expect($featuredFoods)->toBeInstanceOf(Collection::class);
});

it('opens the in-app scanner from the hero and the scan card for verified accounts', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('canUseApp', true)
        ->assertSee('snap_to_track_app_cta_click')
        ->assertDontSee('"snap_to_track_cta_click"', false);

    expect(mb_substr_count((string) $response->getContent(), 'href="'.route('snap-to-track.index').'"'))->toBe(2);
});

it('keeps the free public scanner for guests and unverified accounts', function (bool $signedIn): void {
    if ($signedIn) {
        $this->actingAs(User::factory()->unverified()->create());
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('canUseApp', false)
        ->assertSee('snap_to_track_cta_click')
        ->assertDontSee(route('snap-to-track.index'), false);
})->with([
    'guest' => [false],
    'unverified account' => [true],
]);
