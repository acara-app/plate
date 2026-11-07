<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Data\Billing\AiAllowance;
use App\Enums\AiAllowanceReset;
use App\Utilities\StaticUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class UsageLimitExceededException extends RuntimeException
{
    public function __construct(public readonly AiAllowance $allowance)
    {
        parent::__construct(sprintf('AI allowance used up on %s tier.', $this->allowance->tier->value));
    }

    /**
     * @return array<mixed>
     */
    public function toPayload(): array
    {
        return ['error' => 'usage_limit_exceeded', ...$this->allowance->toArray()];
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse($this->toPayload(), Response::HTTP_PAYMENT_REQUIRED);
    }

    public function userMessage(): string
    {
        $message = $this->allowance->resets === AiAllowanceReset::Daily
            ? "⏳ Today's AI allowance is used up. It resets at midnight."
            : "⏳ This month's free AI is used up. It resets on {$this->allowance->resetsAt->format('F j')}.";

        if (! $this->allowance->canUpgrade) {
            return $message;
        }

        return $message."\n\nNeed more? See plans:\n".StaticUrl::checkoutUrl();
    }
}
