<?php

declare(strict_types=1);

use App\Http\Controllers\UserTimezoneController;
use App\Models\User;

covers(UserTimezoneController::class);

it('guest can update timezone', function (): void {
    $response = $this->post(route('profile.timezone.update'), [
        'timezone' => 'Europe/Madrid',
    ]);

    $response->assertSuccessful();
});

it('logged user can update timezone', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('profile.timezone.update'), [
        'timezone' => 'Europe/Madrid',
    ]);

    $response->assertSuccessful();

    expect($user->fresh()->timezone)->toBe('Europe/Madrid');
});

it('timezone must be valid', function (): void {
    $response = $this->post(route('profile.timezone.update'), [
        'timezone' => 'Nuno/Maduro',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('timezone');
});
