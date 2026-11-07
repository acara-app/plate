<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Data\Billing\AiAllowance;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\User;

final readonly class EnforceAiUsageLimit
{
    public function __construct(private ResolveAiAllowance $resolveAiAllowance) {}

    /**
     * @throws UsageLimitExceededException when another AI call would go past the user's AI allowance.
     */
    public function handle(User $user): void
    {
        $allowance = $this->resolveAiAllowance->handle($user);

        throw_if($allowance instanceof AiAllowance && ! $allowance->allows(config()->float('plate.ai_call_estimate_usd')), UsageLimitExceededException::class, $allowance);
    }
}
