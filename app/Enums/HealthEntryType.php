<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthEntryType: string
{
    case Glucose = 'glucose';
    case Food = 'food';
    case Insulin = 'insulin';
    case Meds = 'meds';
    case Vitals = 'vitals';
    case Exercise = 'exercise';
}
