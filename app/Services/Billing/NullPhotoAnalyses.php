<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\User;
use Closure;

final readonly class NullPhotoAnalyses implements ManagesPhotoAnalyses
{
    public function enabled(): bool
    {
        return false;
    }

    public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
    {
        return new PhotoEntitlement;
    }

    public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
    {
        return $analyze(PhotoModel::standard());
    }
}
