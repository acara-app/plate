<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;

it('asks whether a weight typed into the kilograms field was meant as pounds', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'age' => 30,
        'date_of_birth' => null,
        'height' => null,
        'weight' => null,
        'sex' => Sex::Male,
    ]);
    $this->actingAs($user);

    visit(route('onboarding.biometrics.show', absolute: false))->withLocale('en-GB')
        ->assertSee('Height (cm)')
        ->fill('#height', '175')
        ->fill('#weight', '193')
        ->press('Continue')
        ->assertSee('Did you mean 193 lb?')
        ->click('button:has-text("Yes, 193 lb")')
        ->assertSee('Weight (lb)')
        ->assertValue('#height_feet', '5')
        ->assertValue('#height_inches', '8.9')
        ->press('Continue')
        ->assertPathIs(route('onboarding.identity.show', absolute: false))
        ->assertNoJavaScriptErrors();

    expect($user->profile()->first())
        ->weight->toBe(87.54)
        ->height->toBe(175.01);
});
