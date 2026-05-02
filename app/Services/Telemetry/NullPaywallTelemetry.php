<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\User;

final readonly class NullPaywallTelemetry implements EmitsPaywallEvents
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @codeCoverageIgnore
     */
    public function emit(PaywallEvent $event, ?User $user = null, array $payload = []): void {}
}
