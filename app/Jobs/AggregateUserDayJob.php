<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class AggregateUserDayJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly int $userId,
        private readonly ?string $overrideDate = null,
    ) {
        $this->queue = 'health-aggregate';
    }

    public function handle(AggregateHealthDailySamplesAction $action): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $localDate = $this->resolveLocalDate($user);

        $upserted = $action->handle($user, $localDate);

        Log::channel($this->logChannel())->info('health_aggregate.user_day_completed', [
            'user_id' => $this->userId,
            'local_date' => $localDate->toDateString(),
            'upserted' => $upserted,
        ]);
    }

    private function resolveLocalDate(User $user): CarbonImmutable
    {
        if ($this->overrideDate !== null) {
            return CarbonImmutable::parse($this->overrideDate);
        }

        return CarbonImmutable::now($user->resolveTimezone())->subDay()->startOfDay();
    }

    private function logChannel(): string
    {
        return config()->has('logging.channels.health_aggregate') ? 'health_aggregate' : 'stack';
    }
}
