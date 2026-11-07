<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Actions\Billing\ResolvePhotoAnalysisReservation;
use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;

final readonly class DatabaseFailedPhotoAnalysisResolver implements ResolvesFailedPhotoAnalysis
{
    public function __construct(private ResolvePhotoAnalysisReservation $reservations) {}

    public function handle(PhotoAnalysisContext $context): ?FoodAnalysisData
    {
        $result = $this->reservations->handle(
            $context->user?->id,
            $context->guestId,
            $context->requestId,
            $context->fingerprint,
            $context->source,
        );

        if ($result === null) {
            return null;
        }

        return FoodAnalysisData::from(json_decode($result, true, flags: JSON_THROW_ON_ERROR));
    }
}
