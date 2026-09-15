<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\User;
use Closure;

interface ManagesPhotoAnalyses
{
    public function enabled(): bool;

    public function entitlement(?User $user, ?string $guestId): PhotoEntitlement;

    /** @param Closure(PhotoModel): FoodAnalysisData $analyze */
    public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData;
}
