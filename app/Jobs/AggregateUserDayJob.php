<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AggregateUserDayJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $userId,
        private readonly ?string $overrideDate = null,
    ) {
        $this->queue = 'default';
    }

    public function uniqueId(): string
    {
        return $this->userId.':'.($this->overrideDate ?? 'yesterday');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
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

    public function failed(Throwable $exception): void
    {
        Log::channel($this->logChannel())->error('health_aggregate.user_day_failed', [
            'user_id' => $this->userId,
            'override_date' => $this->overrideDate,
            'error' => $exception->getMessage(),
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
