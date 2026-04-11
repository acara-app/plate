<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SpikeRiskLevel;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class SpikePredictionData extends Data
{
    public function __construct(
        public string $food,
        public SpikeRiskLevel $riskLevel,
        #[MapInputName('estimated_gl')]
        public int $estimatedGlycemicLoad,
        public string $explanation,
        public string $smartFix,
        public int $spikeReductionPercentage,
    ) {}
}
