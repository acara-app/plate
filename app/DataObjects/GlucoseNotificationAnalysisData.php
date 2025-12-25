<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use Spatie\LaravelData\Data;

final class GlucoseNotificationAnalysisData extends Data
{
    /**
     * @param  array<int, string>  $concerns
     */
    public function __construct(
        public bool $shouldNotify,
        public array $concerns,
        public GlucoseAnalysisData $analysisData,
    ) {}
}
