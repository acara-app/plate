<?php

declare(strict_types=1);

namespace App\Data;

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
     * @param  array{title: string, body: string, badge: string, tone: string, limit_mg: int|null}  $verdictCard
     * @param  array{label: string, value_label: string, limit_mg: int|null, max_mg: int, tone: string, caption: string}  $limitGauge
     * @param  array{title: string, items: array<int, string>}  $guidanceList
     * @param  array{title: string, body: string}|null  $contextNote
     * @param  array{title: string, body: string, items: array<int, string>}  $safetyNote
     */
    public function __construct(
        public string $summary,
        public array $verdictCard,
        public array $limitGauge,
        public array $guidanceList,
        public ?array $contextNote,
        public array $safetyNote,
    ) {}
}
