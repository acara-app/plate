<?php

declare(strict_types=1);

use App\Enums\GoalChoice;

it('has correct values', function (): void {
    expect(GoalChoice::Spikes->value)->toBe('spikes')
        ->and(GoalChoice::WeightLoss->value)->toBe('weight_loss')
        ->and(GoalChoice::HeartHealth->value)->toBe('heart_health')
        ->and(GoalChoice::BuildMuscle->value)->toBe('build_muscle')
        ->and(GoalChoice::HealthyEating->value)->toBe('healthy_eating');
});

it('returns correct labels', function (GoalChoice $goal, string $label): void {
    expect($goal->label())->toBe($label);
})->with([
    'Spikes' => [GoalChoice::Spikes, 'Control Spikes'],
    'Weight Loss' => [GoalChoice::WeightLoss, 'Deep Weight Loss'],
    'Heart Health' => [GoalChoice::HeartHealth, 'Heart Health'],
    'Build Muscle' => [GoalChoice::BuildMuscle, 'Build Muscle'],
    'Healthy Eating' => [GoalChoice::HealthyEating, 'Just Healthy Eating'],
]);

it('returns correct descriptions', function (GoalChoice $goal, string $description): void {
    expect($goal->description())->toBe($description);
})->with([
    'Spikes' => [GoalChoice::Spikes, 'Focus: Stable Blood Sugar'],
    'Weight Loss' => [GoalChoice::WeightLoss, 'Focus: Burning Fat'],
    'Heart Health' => [GoalChoice::HeartHealth, 'Focus: Cholesterol/BP'],
    'Build Muscle' => [GoalChoice::BuildMuscle, 'Focus: Strength & Hypertrophy'],
    'Healthy Eating' => [GoalChoice::HealthyEating, 'Maintenance / No specific goal'],
]);
