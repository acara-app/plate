<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\SpikeRiskLevel;
use Spatie\LaravelData\Data;

final class SpikePredictionData extends Data
{
    public function __construct(
        public string $food,
        public SpikeRiskLevel $riskLevel,
        public int $estimatedGlycemicLoad,
        public string $explanation,
        public string $smartFix,
        public int $spikeReductionPercentage,
    ) {}
}
