<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SpikeRiskLevel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
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

    /**
     * @return array<string, Type>
     */
    public static function jsonSchema(JsonSchema $schema): array
    {
        return [
            'risk_level' => $schema->string()->enum(SpikeRiskLevel::class)->required(),
            'estimated_gl' => $schema->integer()->min(0)->max(100)->required(),
            'explanation' => $schema->string()->required(),
            'smart_fix' => $schema->string()->required(),
            'spike_reduction_percentage' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }
}
