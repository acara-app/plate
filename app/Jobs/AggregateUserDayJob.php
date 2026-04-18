<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\SleepSessionAggregator;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

/** @codeCoverageIgnore */
#[MaxExceptions(3)]
#[Timeout(120)]
#[Tries(3)]
final class AggregateUserDayJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $userId,
        private readonly string $utcDate,
    ) {
        $this->queue = 'default';
    }

    public function uniqueId(): string
    {
        return $this->userId.':'.$this->utcDate;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
    ): void {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $utcDate = CarbonImmutable::parse($this->utcDate, 'UTC')->startOfDay();

        $healthAggregator->handle($user, $utcDate);
        $sleepAggregator->handle($user, $utcDate);
    }
}
