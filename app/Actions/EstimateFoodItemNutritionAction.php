<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Data\FoodItemEstimateData;
use App\Models\User;
use App\Utilities\LanguageUtil;

final readonly class EstimateFoodItemNutritionAction
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private ResolvesAgentModel $agentModels,
        private FoodItemEstimatorAgent $agent,
    ) {}

    public function handle(User $user, string $name, ?float $grams): FoodItemEstimateData
    {
        $this->enforceAiUsageLimit->handle($user, $this->agentModels->resolve(FoodItemEstimatorAgent::class, $user));

        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($user->locale);

        return $this->agent
            ->withLanguage($language, $languageCode)
            ->estimate($name, $grams, $user)
            ->normalized();
    }
}
