<?php

declare(strict_types=1);

namespace App\Enums;


enum AgenMode: string
{
    case Ask = 'ask';
    case GenerateMealPlan = 'generate-meal-plan';
}
