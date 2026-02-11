<?php

declare(strict_types=1);

namespace App\Ai\Contracts;

use App\Ai\Agents\SpikePredictorAgent;
use App\DataObjects\SpikePredictionData;
use Illuminate\Container\Attributes\Bind;

#[Bind(SpikePredictorAgent::class)]
interface PredictsGlucoseSpikes
{
    public function predict(string $food): SpikePredictionData;
}
