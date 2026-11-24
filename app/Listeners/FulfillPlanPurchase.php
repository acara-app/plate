<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Billing\RecordPlanPurchasePayment;
use Laravel\Cashier\Events\WebhookReceived;

final readonly class FulfillPlanPurchase
{
    public function __construct(private RecordPlanPurchasePayment $recordPayment) {}

    public function handle(WebhookReceived $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        $object = data_get($payload, 'data.object');

        if (blank(config('cashier.webhook.secret')) || ! is_array($object)) {
            return;
        }

        $subscriptionId = $object['id'] ?? null;

        match ($payload['type'] ?? null) {
            'invoice.paid', 'invoice.payment_succeeded' => $this->recordPayment->handle($object),
            'customer.subscription.deleted' => is_string($subscriptionId) ? $this->recordPayment->cancel($subscriptionId) : null,
            default => null,
        };
    }
}
