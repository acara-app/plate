<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Billing\SnapFunnel;
use Laravel\Cashier\Events\WebhookReceived;

final readonly class RecordSnapFirstPayment
{
    public function __construct(private SnapFunnel $funnel) {}

    public function handle(WebhookReceived $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        $invoice = data_get($payload, 'data.object');

        if (blank(config('cashier.webhook.secret')) || ! in_array($payload['type'] ?? null, ['invoice.paid', 'invoice.payment_succeeded'], true) || ! is_array($invoice)) {
            return;
        }

        /** @var array<string, mixed> $invoice */
        $this->funnel->recordFirstPayment($invoice, ($payload['livemode'] ?? false) === true);
    }
}
