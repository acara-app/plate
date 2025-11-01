<?php

declare(strict_types=1);

namespace App\Enums;

enum Sex: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
}
