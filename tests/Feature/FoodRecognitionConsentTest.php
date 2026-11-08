<?php

declare(strict_types=1);

use App\Actions\RecordConsent;
use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Enums\EstimateValidationStatus;
use App\Http\Controllers\HealthEntry\ListHealthEntryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPrivacyController;
use App\Http\Requests\UpdateUserPrivacyRequest;
use App\Models\Consent;
use App\Models\MealEstimate;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;

covers(
    RecordConsent::class,
    UserPrivacyController::class,
    UpdateUserPrivacyRequest::class,
    UserController::class,
    ListHealthEntryController::class,
    Consent::class,
    User::class,
);

it('records the permission at sign-up only when the box is ticked', function (bool $ticked): void {
    Event::fake([Registered::class]);

    $this->fromRoute('register')->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password1234',
        'accepted_disclaimer' => '1',
        ...$ticked ? ['improve_food_recognition' => '1'] : [],
    ])->assertRedirectToRoute('dashboard');

    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($user->consentDecision(ConsentPurpose::ImproveFoodRecognition))->toBe($ticked ? true : null)
        ->and($user->consents()->get()->map(fn (Consent $consent): array => [$consent->source, $consent->version])->all())
        ->toBe($ticked ? [[ConsentSource::SignUp, ConsentPurpose::ImproveFoodRecognition->version()]] : []);
})->with(['ticked' => [true], 'left unticked' => [false]]);

it('lets people turn the permission on and off in Settings, keeping each change', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user-privacy.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('user-privacy/edit')->where('improveFoodRecognition', false));

    $this->patch(route('user-privacy.update'), ['improve_food_recognition' => '1'])->assertRedirect();
    $this->patch(route('user-privacy.update'), ['improve_food_recognition' => '1'])->assertRedirect();
    $this->patch(route('user-privacy.update'), ['improve_food_recognition' => '0'])->assertRedirect();

    expect($user->consents()->oldest('id')->get()->map(fn (Consent $consent): array => [$consent->granted, $consent->source])->all())
        ->toBe([[true, ConsentSource::Settings], [false, ConsentSource::Settings]]);

    $this->get(route('user-privacy.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('improveFoodRecognition', false));
});

it('asks once on the logbook after someone corrects a meal', function (): void {
    $user = User::factory()->create();
    MealEstimate::factory()->for($user)->create(['validation_status' => EstimateValidationStatus::UserCorrected]);

    $this->actingAs($user)
        ->get(route('health-entries.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('askToImproveFoodRecognition', true));

    $this->patch(route('user-privacy.update'), ['improve_food_recognition' => false, 'source' => 'meal_correction'])->assertRedirect();

    expect($user->consents()->sole())
        ->granted->toBeFalse()
        ->source->toBe(ConsentSource::MealCorrection);

    $this->get(route('health-entries.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('askToImproveFoodRecognition', false));
});
