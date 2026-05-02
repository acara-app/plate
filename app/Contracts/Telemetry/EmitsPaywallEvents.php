<?php

declare(strict_types=1);

namespace App\Contracts\Telemetry;

use App\Enums\Telemetry\PaywallEvent;
use App\Models\User;

interface EmitsPaywallEvents
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function emit(PaywallEvent $event, ?User $user = null, array $payload = []): void;
}
