<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\SnapFunnelStage;
use App\Models\User;
use App\Services\Billing\SnapFunnel;
use Illuminate\Auth\Events\Login;

final readonly class RecordSnapJourneySignIn
{
    public function __construct(private SnapFunnel $funnel) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->funnel->record(
                SnapFunnelStage::AuthCompleted,
                (string) $event->user->id,
                ['method' => $event->user->wasRecentlyCreated ? 'register' : 'login'],
                $event->user,
            );
        }
    }
}
