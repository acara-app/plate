<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telemetry;

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Http\Requests\Telemetry\RecordPaywallEventRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class RecordPaywallEventController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private EmitsPaywallEvents $telemetry,
    ) {}

    public function __invoke(RecordPaywallEventRequest $request): JsonResponse
    {
        $this->telemetry->emit(
            event: $request->event(),
            user: $this->user,
            payload: [
                ...$request->payload(),
                'surface' => $request->payload()['surface'] ?? 'client',
            ],
        );

        return new JsonResponse(['accepted' => true], 202);
    }
}
