<?php

declare(strict_types=1);

namespace App\Enums;

enum GatedFeature: string
{
    case MealPlanner = 'meal_planner';
    case ImageAnalysis = 'image_analysis';
    case Memory = 'memory';
    case HealthSync = 'health_sync';

    public function requiredTier(): SubscriptionTier
    {
        return match ($this) {
            self::MealPlanner, self::ImageAnalysis => SubscriptionTier::Basic,
            self::Memory, self::HealthSync => SubscriptionTier::Plus,
        };
    }
}
