<?php

declare(strict_types=1);

use App\Enums\AllergySeverity;
use App\Enums\UserProfileAttributeCategory;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;

covers(UserProfileAttribute::class);

it('belongs to user profile', function (): void {
    $profile = UserProfile::factory()->create();
    $attribute = UserProfileAttribute::factory()->allergy('Peanuts')->create([
        'user_profile_id' => $profile->id,
    ]);

    expect($attribute->userProfile)
        ->toBeInstanceOf(UserProfile::class)
        ->id->toBe($profile->id);
});

it('casts category to enum', function (): void {
    $attribute = UserProfileAttribute::factory()->allergy()->create();

    expect($attribute->category)->toBe(UserProfileAttributeCategory::Allergy);
});

it('casts severity to enum', function (): void {
    $attribute = UserProfileAttribute::factory()->allergy('Peanuts', AllergySeverity::Severe)->create();

    expect($attribute->severity)->toBe(AllergySeverity::Severe);
});

it('casts metadata to array', function (): void {
    $attribute = UserProfileAttribute::factory()->medication('Metformin', [
        'dosage' => '500mg',
        'frequency' => 'twice daily',
    ])->create();

    expect($attribute->metadata)
        ->toBeArray()
        ->toHaveKey('dosage', '500mg')
        ->toHaveKey('frequency', 'twice daily');
});

it('severity is nullable', function (): void {
    $attribute = UserProfileAttribute::factory()->healthCondition()->create();

    expect($attribute->severity)->toBeNull();
});

it('metadata is nullable', function (): void {
    $attribute = UserProfileAttribute::factory()->allergy()->create();

    expect($attribute->metadata)->toBeNull();
});

it('factory creates valid allergy', function (): void {
    $attribute = UserProfileAttribute::factory()->allergy('Shellfish', AllergySeverity::Moderate)->create();

    expect($attribute)
        ->category->toBe(UserProfileAttributeCategory::Allergy)
        ->value->toBe('Shellfish')
        ->severity->toBe(AllergySeverity::Moderate);
});

it('factory creates valid health condition', function (): void {
    $attribute = UserProfileAttribute::factory()->healthCondition('Hypertension')->create();

    expect($attribute)
        ->category->toBe(UserProfileAttributeCategory::HealthCondition)
        ->value->toBe('Hypertension');
});

it('factory creates valid medication with metadata', function (): void {
    $attribute = UserProfileAttribute::factory()->medication('Metformin')->create();

    expect($attribute)
        ->category->toBe(UserProfileAttributeCategory::Medication)
        ->value->toBe('Metformin')
        ->metadata->toHaveKey('dosage')
        ->metadata->toHaveKey('frequency')
        ->metadata->toHaveKey('purpose');
});
