<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthAggregationFunction: string
{
    case Sum = 'sum';
    case Avg = 'avg';
    case WeightedAvg = 'weighted_avg';
    case Min = 'min';
    case Max = 'max';
    case Last = 'last';
    case Count = 'count';
    case None = 'none';
}
