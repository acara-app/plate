<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Enums\GatedFeature;
use App\Enums\SubscriptionTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class FeatureGateException extends RuntimeException
{
    public function __construct(
        public readonly GatedFeature $feature,
        public readonly SubscriptionTier $currentTier,
        public readonly SubscriptionTier $requiredTier,
    ) {
        parent::__construct(sprintf(
            '%s is gated to %s tier (current tier: %s).',
            $this->feature->value,
            $this->requiredTier->value,
            $this->currentTier->value,
        ));
    }

    /**
     * @return array{
     *     error: string,
     *     feature: string,
     *     current_tier: string,
     *     current_tier_label: string,
     *     required_tier: string,
     *     required_tier_label: string
     * }
     */
    public function toPayload(): array
    {
        return [
            'error' => 'feature_gated',
            'feature' => $this->feature->value,
            'current_tier' => $this->currentTier->value,
            'current_tier_label' => $this->currentTier->label(),
            'required_tier' => $this->requiredTier->value,
            'required_tier_label' => $this->requiredTier->label(),
        ];
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse($this->toPayload(), Response::HTTP_PAYMENT_REQUIRED);
    }
}
