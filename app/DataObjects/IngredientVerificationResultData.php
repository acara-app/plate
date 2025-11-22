<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class IngredientVerificationResultData extends Data
{
    /**
     * @param  DataCollection<int, VerifiedIngredientData>  $verifiedIngredients
     */
    public function __construct(
        public DataCollection $verifiedIngredients,
        public int $totalVerified,
        public bool $verificationSuccess,
        public float $verificationRate,
        public bool $verified,
        public string $source,
    ) {}
}
