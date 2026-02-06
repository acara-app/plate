<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentMode: string
{
    case Ask = 'ask';
    case GenerateMealPlan = 'generate-meal-plan';
}
