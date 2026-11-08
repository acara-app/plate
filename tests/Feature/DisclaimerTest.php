<?php

declare(strict_types=1);

use App\Actions\RecordSignUpAgreements;
use App\Enums\ConsentPurpose;
use App\Http\Controllers\DisclaimerController;
use App\Http\Controllers\UserController;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(DisclaimerController::class, UserController::class, RecordSignUpAgreements::class);

function expectSignUpAgreementsRecorded(User $user): void
{
    expect($user->accepted_disclaimer_at)->not->toBeNull()
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->privacy_accepted_at)->not->toBeNull()
        ->and($user->consent_version)->toBe(config('plate.terms_version'))
        ->and($user->privacy_version)->toBe(config('plate.privacy_policy_version'))
        ->and($user->consentDecision(ConsentPurpose::ProcessHealthData))->toBeTrue();
}

it('renders disclaimer page for user who has not accepted', function (): void {
    $this->withoutVite();

    $user = User::factory()->withoutDisclaimer()->create();

    actingAs($user)
        ->get(route('disclaimer.show'))
        ->assertOk();
});

it('requires accepted_disclaimer checkbox during registration', function (): void {
    post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('accepted_disclaimer');
});

it('records the disclaimer, terms, privacy policy and health-data consent at email sign-up', function (): void {
    post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'accepted_disclaimer' => '1',
        'health_data_consent' => '1',
    ])->assertRedirect();

    assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    expectSignUpAgreementsRecorded(User::query()->where('email', 'test@example.com')->sole());
});

it('redirects to disclaimer page when user has not accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('disclaimer.show'));
});

it('allows access to dashboard when disclaimer is accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => now(),
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('preserves the intended destination through the disclaimer gate', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('disclaimer.show'));

    expect(session('url.intended'))->toBe(route('dashboard'));
});

it('redirects to the intended destination after accepting the disclaimer', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)->get(route('dashboard'));

    actingAs($user)
        ->post(route('disclaimer.accept'), ['accepted' => '1', 'health_data_consent' => '1'])
        ->assertRedirect(route('dashboard'));
});

it('records the same agreements when a Google or Apple sign-up accepts the disclaimer', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->post(route('disclaimer.accept'), ['accepted' => '1', 'health_data_consent' => '1'])
        ->assertRedirect();

    expectSignUpAgreementsRecorded($user->refresh());
});

it('requires the health-data consent at sign-up and on the disclaimer step', function (): void {
    post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'accepted_disclaimer' => '1',
    ])->assertSessionHasErrors(['health_data_consent' => __('auth.register.health_data_consent_required')]);

    actingAs(User::factory()->withoutDisclaimer()->create())
        ->post(route('disclaimer.accept'), ['accepted' => '1'])
        ->assertSessionHasErrors('health_data_consent');

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
