<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsentSource: string
{
    case SignUp = 'sign_up';
    case MealCorrection = 'meal_correction';
    case Settings = 'settings';
    case Dashboard = 'dashboard';
}
