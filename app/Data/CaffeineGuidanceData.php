<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\CaffeineGuidance\ConditionSectionData;
use App\Data\CaffeineGuidance\GuidanceListData;
use App\Data\CaffeineGuidance\LimitGaugeData;
use App\Data\CaffeineGuidance\SafetyNoteData;
use App\Data\CaffeineGuidance\TimingCardData;
use App\Data\CaffeineGuidance\VerdictCardData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @codeCoverageIgnore
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CaffeineGuidanceData extends Data
{
    /**
     * @param  array<int, ConditionSectionData>|null  $conditionSections
     */
    public function __construct(
        public string $summary,
        public VerdictCardData $verdictCard,
        public LimitGaugeData $limitGauge,
        public TimingCardData $timingCard,
        public GuidanceListData $guidanceList,
        public SafetyNoteData $safetyNote,
        #[DataCollectionOf(ConditionSectionData::class)]
        public ?array $conditionSections = [],
    ) {}
}
