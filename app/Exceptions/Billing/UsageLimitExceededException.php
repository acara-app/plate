<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Enums\SubscriptionTier;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class UsageLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $limitType,
        public readonly SubscriptionTier $tier,
        public readonly int $currentCredits,
        public readonly int $limitCredits,
        public readonly CarbonInterface $resetsAt,
    ) {
        parent::__construct(sprintf(
            'Usage limit exceeded for %s window on %s tier (%d/%d credits).',
            $this->limitType,
            $this->tier->value,
            $this->currentCredits,
            $this->limitCredits,
        ));
    }

    /**
     * @return array{
     *     error: string,
     *     limit_type: string,
     *     tier: string,
     *     tier_label: string,
     *     current_credits: int,
     *     limit_credits: int,
     *     resets_at: string,
     *     resets_in: string
     * }
     */
    public function toPayload(): array
    {
        return [
            'error' => 'usage_limit_exceeded',
            'limit_type' => $this->limitType,
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'current_credits' => $this->currentCredits,
            'limit_credits' => $this->limitCredits,
            'resets_at' => $this->resetsAt->toIso8601String(),
            'resets_in' => $this->formatResetsIn(),
        ];
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse($this->toPayload(), Response::HTTP_PAYMENT_REQUIRED);
    }

    private function formatResetsIn(): string
    {
        $diff = now()->diff($this->resetsAt);

        if ($diff->d > 0) {
            return $diff->d.' days '.$diff->h.' hours';
        }

        if ($diff->h > 0) {
            return $diff->h.' hours '.$diff->i.' minutes';
        }

        return $diff->i.' minutes';
    }
}
