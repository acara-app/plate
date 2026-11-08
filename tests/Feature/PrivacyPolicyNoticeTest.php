<?php

declare(strict_types=1);

use App\Actions\AcknowledgePrivacyPolicy;
use App\Enums\ConsentPurpose;
use App\Http\Controllers\AcknowledgePrivacyPolicyController;
use App\Http\Controllers\DashboardController;
use App\Models\Consent;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

covers(
    DashboardController::class,
    AcknowledgePrivacyPolicyController::class,
    AcknowledgePrivacyPolicy::class,
    User::class,
);

it('asks people who joined before the privacy update to acknowledge it once', function (): void {
    $user = User::factory()->create(['created_at' => '2026-10-01 09:00:00', 'privacy_version' => '1.0']);
    Consent::factory()->for($user)->create(['purpose' => ConsentPurpose::ProcessHealthData]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('showPrivacyUpdate', true));

    $this->post(route('privacy-policy.acknowledge'))->assertRedirect();

    expect($user->refresh())
        ->privacy_version->toBe(config('plate.privacy_policy_version'))
        ->privacy_accepted_at->not->toBeNull();

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('showPrivacyUpdate', false));
});

it('does not ask people who joined under the current privacy policy', function (): void {
    $this->actingAs(User::factory()->create(['created_at' => '2026-11-20 09:00:00']))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('showPrivacyUpdate', false));
});
