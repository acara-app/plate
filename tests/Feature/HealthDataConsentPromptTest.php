<?php

declare(strict_types=1);

use App\Actions\AcceptHealthDataConsent;
use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Http\Controllers\AcceptHealthDataConsentController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

covers(DashboardController::class, AcceptHealthDataConsentController::class, AcceptHealthDataConsent::class, User::class);

it('asks existing accounts once for health-data consent, ahead of the privacy notice', function (): void {
    $user = User::factory()->create(['created_at' => '2026-10-01 09:00:00']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('showHealthDataConsent', true)->where('showPrivacyUpdate', false));

    $this->post(route('health-data-consent.store'))->assertRedirect();

    $consent = $user->consents()->sole();

    expect([$consent->purpose, $consent->granted, $consent->source])->toBe([ConsentPurpose::ProcessHealthData, true, ConsentSource::Dashboard])
        ->and($user->refresh()->privacy_version)->toBe(config('plate.privacy_policy_version'));

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('showHealthDataConsent', false)->where('showPrivacyUpdate', false));
});
