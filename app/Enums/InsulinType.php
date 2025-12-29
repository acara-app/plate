<?php

declare(strict_types=1);

namespace App\Enums;

enum InsulinType: string
{
    case Basal = 'basal';
    case Bolus = 'bolus';
    case Mixed = 'mixed';
}
