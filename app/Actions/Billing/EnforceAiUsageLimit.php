<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Data\Ai\AgentModelData;
use App\Data\Billing\AiAllowance;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\User;

final readonly class EnforceAiUsageLimit
{
    public function __construct(private ResolveAiAllowance $resolveAiAllowance) {}

    /**
     * @throws UsageLimitExceededException when the call would go past the user's AI allowance.
     */
    public function handle(User $user, ?AgentModelData $model = null): void
    {
        $allowance = $this->resolveAiAllowance->handle($user);

        throw_if($allowance instanceof AiAllowance && ! $allowance->allows($this->estimateCallCost($model)), UsageLimitExceededException::class, $allowance);
    }

    private function estimateCallCost(?AgentModelData $model): float
    {
        /** @var array{token_budget: array{input: int, output: int}, fallback_estimate: float} $config */
        $config = config()->array('plate.ai_usage_preflight', [
            'token_budget' => ['input' => 2_000, 'output' => 1_000],
            'fallback_estimate' => 0.01,
        ]);

        if (! $model instanceof AgentModelData) {
            return (float) $config['fallback_estimate'];
        }

        $pricing = $model->pricing;
        $tokens = $config['token_budget'];

        return ((int) $tokens['input']) / 1_000_000 * $pricing['input']
            + ((int) $tokens['output']) / 1_000_000 * $pricing['output'];
    }
}
