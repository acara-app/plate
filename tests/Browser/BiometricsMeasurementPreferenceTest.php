<?php

declare(strict_types=1);

use App\Enums\MeasurementSystem;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('restores the selected units and measurement values after saving and reopening biometrics', function (): void {
    Http::preventStrayRequests();

    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create([
        'age' => 30,
        'height' => 177.8,
        'weight' => 90.72,
        'sex' => Sex::Male,
    ]);

    $this->actingAs($user);

    $page = visit(route('onboarding.biometrics.show', absolute: false))
        ->withLocale('en-GB');

    $page->assertValue('height', '177.8')
        ->click('button:has-text("lb, ft, in")')
        ->assertValue('weight', '200')
        ->assertValue('height_feet', '5')
        ->assertValue('height_inches', '10')
        ->press('Continue')
        ->assertPathIs('/onboarding/identity')
        ->navigate(route('onboarding.biometrics.show', absolute: false))
        ->assertAttribute('button:has-text("lb, ft, in")', 'aria-checked', 'true')
        ->assertValue('weight', '200')
        ->assertValue('height_feet', '5')
        ->assertValue('height_inches', '10')
        ->assertNoJavaScriptErrors();

    expect($profile->refresh())->measurement_system->toBe(MeasurementSystem::Imperial);

    $page->click('button:has-text("kg, cm")')
        ->press('Continue')
        ->assertPathIs('/onboarding/identity')
        ->navigate(route('onboarding.biometrics.show', absolute: false))
        ->assertAttribute('button:has-text("kg, cm")', 'aria-checked', 'true')
        ->assertValue('weight', '90.72')
        ->assertValue('height', '177.8')
        ->assertNoJavaScriptErrors();

    expect($profile->refresh())
        ->measurement_system->toBe(MeasurementSystem::Metric)
        ->weight->toBe(90.72)
        ->height->toBe(177.8);
});
