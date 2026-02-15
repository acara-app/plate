<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentType: string
{
    case Nutrition = 'nutrition';
    case HealthCoach = 'health-coach';
    case PersonalTrainer = 'personal-trainer';
}
