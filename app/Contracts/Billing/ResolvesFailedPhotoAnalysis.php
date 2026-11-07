<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;

interface ResolvesFailedPhotoAnalysis
{
    public function handle(PhotoAnalysisContext $context): ?FoodAnalysisData;
}
