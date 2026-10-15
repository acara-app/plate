<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\SpikePredictorAgent;
use App\Data\SpikePredictionData;
use App\Models\User;

final readonly class PredictGlucoseSpikeAction
{
    public function __construct(
        private SpikePredictorAgent $agent,
    ) {}

    public function handle(string $food, ?User $user = null): SpikePredictionData
    {
        return $this->agent->predict($food, $user);
    }
}
