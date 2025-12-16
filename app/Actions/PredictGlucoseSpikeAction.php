<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\SpikePredictorAgent;
use App\DataObjects\SpikePredictionData;

final readonly class PredictGlucoseSpikeAction
{
    public function __construct(
        private SpikePredictorAgent $agent,
    ) {}

    public function handle(string $food): SpikePredictionData
    {
        return $this->agent->predict($food);
    }
}
