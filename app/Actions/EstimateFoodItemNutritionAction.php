<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Data\FoodItemEstimateData;
use App\Models\User;

final readonly class EstimateFoodItemNutritionAction
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private ResolvesAgentModel $agentModels,
        private FoodItemEstimatorAgent $agent,
    ) {}

    public function handle(User $user, string $name, ?float $grams, ?string $portion): FoodItemEstimateData
    {
        $this->enforceAiUsageLimit->handle($user, $this->agentModels->resolve(FoodItemEstimatorAgent::class, $user));

        return $this->agent->estimate($name, $grams, $portion, $user)->rounded();
    }
}
