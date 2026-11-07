<?php

declare(strict_types=1);

namespace App\Services\Billing;

final readonly class StripeBilling
{
    public function isConfigured(): bool
    {
        return filled(config('cashier.key')) && filled(config('cashier.secret')) && filled(config('cashier.webhook.secret'));
    }
}
