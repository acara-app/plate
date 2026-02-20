<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use App\Models\UserMedication;
use App\Models\UserProfile;

it('belongs to a user profile', function (): void {
    $userProfile = UserProfile::factory()->create();
    $medication = UserMedication::factory()->for($userProfile)->create();

    expect($medication->userProfile)->toBeInstanceOf(UserProfile::class);
    expect($medication->user_profile_id)->toBe($userProfile->id);
});

it('casts attributes correctly', function (): void {
    $medication = UserMedication::factory()->create([
        'name' => 'Metformin',
        'dosage' => '500mg',
        'frequency' => 'Twice daily',
        'purpose' => 'Blood sugar control',
        'started_at' => '2024-01-15',
    ]);

    expect($medication->id)->toBeInt();
    expect($medication->user_profile_id)->toBeInt();
    expect($medication->name)->toBeString();
    expect($medication->dosage)->toBeString();
    expect($medication->frequency)->toBeString();
    expect($medication->purpose)->toBeString();
    expect($medication->started_at)->toBeInstanceOf(CarbonInterface::class);
    expect($medication->created_at)->toBeInstanceOf(CarbonInterface::class);
    expect($medication->updated_at)->toBeInstanceOf(CarbonInterface::class);
});

it('can have nullable optional fields', function (): void {
    $medication = UserMedication::factory()->create([
        'name' => 'Aspirin',
        'dosage' => null,
        'frequency' => null,
        'purpose' => null,
        'started_at' => null,
    ]);

    expect($medication->name)->toBeString();
    expect($medication->dosage)->toBeNull();
    expect($medication->frequency)->toBeNull();
    expect($medication->purpose)->toBeNull();
    expect($medication->started_at)->toBeNull();
});

it('stores medication name correctly', function (): void {
    $medication = UserMedication::factory()->create([
        'name' => 'Lisinopril',
    ]);

    expect($medication->name)->toBe('Lisinopril');
});

it('stores dosage information correctly', function (): void {
    $medication = UserMedication::factory()->create([
        'dosage' => '10mg',
    ]);

    expect($medication->dosage)->toBe('10mg');
});

it('stores frequency information correctly', function (): void {
    $medication = UserMedication::factory()->create([
        'frequency' => 'Three times daily',
    ]);

    expect($medication->frequency)->toBe('Three times daily');
});

it('stores purpose information correctly', function (): void {
    $medication = UserMedication::factory()->create([
        'purpose' => 'Blood pressure management',
    ]);

    expect($medication->purpose)->toBe('Blood pressure management');
});

it('casts started_at to date correctly', function (): void {
    $startDate = '2023-06-15';
    $medication = UserMedication::factory()->create([
        'started_at' => $startDate,
    ]);

    expect($medication->started_at)->toBeInstanceOf(CarbonInterface::class);
    expect($medication->started_at->format('Y-m-d'))->toBe($startDate);
});
