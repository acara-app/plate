<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\SubscriptionTier;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

final readonly class EmitPaywallEventsFromWebhook
{
    public function __construct(private EmitsPaywallEvents $telemetry) {}

    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;

        /** @var string|null $type */
        $type = $payload['type'] ?? null;

        match ($type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionWritten($payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionWritten(array $payload): void
    {
        /** @var array<string, mixed>|null $object */
        $object = $payload['data']['object'] ?? null;
        if ($object === null) {
            return; // @codeCoverageIgnore
        }

        /** @var string|null $status */
        $status = $object['status'] ?? null;
        if ($status !== 'active' && $status !== 'trialing') {
            return;
        }

        /** @var string|null $customerId */
        $customerId = $object['customer'] ?? null;
        $user = $this->resolveUser($customerId);

        $stripePriceId = $this->resolveStripePriceId($object);
        [$tier, $interval] = $this->resolvePlan($stripePriceId);

        $this->telemetry->emit(
            event: PaywallEvent::CheckoutCompleted,
            user: $user,
            payload: [
                'tier_target' => $tier?->value,
                'tier_target_label' => $tier?->label(),
                'interval' => $interval,
                'stripe_status' => $status,
                'stripe_subscription_id' => $object['id'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionDeleted(array $payload): void
    {
        /** @var array<string, mixed>|null $object */
        $object = $payload['data']['object'] ?? null;
        if ($object === null) {
            return; // @codeCoverageIgnore
        }

        /** @var string|null $customerId */
        $customerId = $object['customer'] ?? null;
        $user = $this->resolveUser($customerId);

        $stripePriceId = $this->resolveStripePriceId($object);
        [$tier] = $this->resolvePlan($stripePriceId);

        $this->telemetry->emit(
            event: PaywallEvent::SubscriptionCanceled,
            user: $user,
            payload: [
                'tier' => $tier?->value,
                'tier_label' => $tier?->label(),
                'stripe_subscription_id' => $object['id'] ?? null,
            ],
        );
    }

    private function resolveUser(?string $stripeCustomerId): ?User
    {
        if ($stripeCustomerId === null || $stripeCustomerId === '') {
            return null; // @codeCoverageIgnore
        }

        $model = Cashier::findBillable($stripeCustomerId);

        return $model instanceof User ? $model : null;
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function resolveStripePriceId(array $object): ?string
    {
        /** @var array<int, array<string, mixed>>|null $items */
        $items = $object['items']['data'] ?? null;
        if (! is_array($items) || $items === []) {
            return null; // @codeCoverageIgnore
        }

        $priceId = $items[0]['price']['id'] ?? null;

        return is_string($priceId) ? $priceId : null;
    }

    /**
     * @return array{0: ?SubscriptionTier, 1: ?string}
     */
    private function resolvePlan(?string $stripePriceId): array
    {
        if ($stripePriceId === null) {
            return [null, null]; // @codeCoverageIgnore
        }

        $product = SubscriptionProduct::query()
            ->where('stripe_price_id', $stripePriceId)
            ->orWhere('yearly_stripe_price_id', $stripePriceId)
            ->first();

        if (! $product instanceof SubscriptionProduct) {
            return [null, null]; // @codeCoverageIgnore
        }

        $interval = $product->yearly_stripe_price_id === $stripePriceId ? 'yearly' : 'monthly';

        return [SubscriptionTier::fromProductName($product->name), $interval];
    }
}
