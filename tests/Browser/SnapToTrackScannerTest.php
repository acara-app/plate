<?php

declare(strict_types=1);

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('gives every photo chosen in the scanner its own analysis request ID', function (): void {
    $user = User::factory()->create(['privacy_version' => config()->string('plate.privacy_policy_version')]);
    Consent::factory()->for($user)->create(['purpose' => ConsentPurpose::ProcessHealthData]);
    UserProfile::factory()->for($user)->create(['onboarding_completed' => true]);
    $this->actingAs($user);
    $photo = UploadedFile::fake()->image('meal.jpg', 64, 64);

    $page = visit(route('snap-to-track.index', absolute: false))->attach('#snap-photo', (string) $photo->getRealPath());
    $firstRequestId = $page->value('input[name="analysis_request_id"]');

    $page->attach('#snap-photo', (string) $photo->getRealPath())
        ->assertValueIsNot('input[name="analysis_request_id"]', $firstRequestId)
        ->assertNoJavaScriptErrors();
});
