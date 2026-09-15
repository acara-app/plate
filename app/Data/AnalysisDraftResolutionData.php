<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AnalysisDraftStatus;
use App\Models\AnalysisDraft;

final readonly class AnalysisDraftResolutionData
{
    public function __construct(
        public AnalysisDraftStatus $status,
        public ?AnalysisDraft $draft = null,
    ) {}

    public function analysis(): ?FoodAnalysisData
    {
        if ($this->draft === null) {
            return null;
        }

        return FoodAnalysisData::from($this->draft->payload);
    }
}
