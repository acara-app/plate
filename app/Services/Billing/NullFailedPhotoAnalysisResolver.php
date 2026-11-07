<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;

final readonly class NullFailedPhotoAnalysisResolver implements ResolvesFailedPhotoAnalysis
{
    public function handle(PhotoAnalysisContext $context): ?FoodAnalysisData
    {
        return null;
    }
}
