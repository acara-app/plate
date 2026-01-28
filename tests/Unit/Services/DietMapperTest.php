<?php

declare(strict_types=1);

use App\Enums\AnimalProductChoice;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Services\DietMapper;

it('maps spikes goal with omnivore and balanced to mediterranean', function (): void {
    $dietType = DietMapper::map(
        GoalChoice::Spikes,
        AnimalProductChoice::Omnivore,
        IntensityChoice::Balanced
    );

    expect($dietType)->toBe(DietType::Mediterranean);
});

it('maps healthy eating to balanced', function (): void {
    $dietType = DietMapper::map(
        GoalChoice::HealthyEating,
        AnimalProductChoice::Omnivore,
        IntensityChoice::Balanced
    );

    expect($dietType)->toBe(DietType::Balanced);
});

it('gets correct activity multiplier for spikes and balanced', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::Spikes,
        IntensityChoice::Balanced
    );

    expect($multiplier)->toBe(1.3);
});

it('gets correct activity multiplier for spikes and aggressive', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::Spikes,
        IntensityChoice::Aggressive
    );

    expect($multiplier)->toBe(1.55);
});

it('gets correct activity multiplier for weight loss balanced', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::WeightLoss,
        IntensityChoice::Balanced
    );

    expect($multiplier)->toBe(1.375);
});

it('gets correct activity multiplier for weight loss aggressive', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::WeightLoss,
        IntensityChoice::Aggressive
    );

    expect($multiplier)->toBe(1.55);
});

it('gets correct activity multiplier for heart health', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::HeartHealth,
        IntensityChoice::Balanced
    );

    expect($multiplier)->toBe(1.3);
});

it('gets correct activity multiplier for build muscle', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::BuildMuscle,
        IntensityChoice::Balanced
    );

    expect($multiplier)->toBe(1.55);
});

it('gets correct activity multiplier for healthy eating', function (): void {
    $multiplier = DietMapper::getActivityMultiplier(
        GoalChoice::HealthyEating,
        IntensityChoice::Balanced
    );

    expect($multiplier)->toBe(1.3);
});
