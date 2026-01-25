<?php

declare(strict_types=1);

namespace App\Enums;

enum GoalChoice: string
{
    case Spikes = 'spikes';
    case WeightLoss = 'weight_loss';
    case HeartHealth = 'heart_health';
    case BuildMuscle = 'build_muscle';
    case HealthyEating = 'healthy_eating';

    public function label(): string
    {
        return match ($this) {
            self::Spikes => 'Control Spikes',
            self::WeightLoss => 'Deep Weight Loss',
            self::HeartHealth => 'Heart Health',
            self::BuildMuscle => 'Build Muscle',
            self::HealthyEating => 'Just Healthy Eating',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Spikes => 'Focus: Stable Blood Sugar',
            self::WeightLoss => 'Focus: Burning Fat',
            self::HeartHealth => 'Focus: Cholesterol/BP',
            self::BuildMuscle => 'Focus: Strength & Hypertrophy',
            self::HealthyEating => 'Maintenance / No specific goal',
        };
    }
}
