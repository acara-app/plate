<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class HealthAggregateMetrics
{
    /**
     * @param  array{user_id: int, local_date: string, types_processed: int, samples_seen: int, upserted: int, duration_ms: float}  $context
     */
    public function runCompleted(array $context): void
    {
        Log::channel($this->channel())->info('health_aggregate.run_completed', $context);
    }

    public function unknownTypeIdentifier(int $userId, string $typeIdentifier, string $localDate): void
    {
        Log::channel($this->channel())->warning('health_aggregate.unknown_type_identifier', [
            'user_id' => $userId,
            'type_identifier' => $typeIdentifier,
            'local_date' => $localDate,
        ]);
    }

    public function unitMismatchDropped(int $userId, string $typeIdentifier, string $fromUnit, string $canonicalUnit): void
    {
        Log::channel($this->channel())->warning('health_aggregate.unit_mismatch_dropped', [
            'user_id' => $userId,
            'type_identifier' => $typeIdentifier,
            'from_unit' => $fromUnit,
            'canonical_unit' => $canonicalUnit,
        ]);
    }

    public function jobFailed(int $userId, string $localDate, string $exceptionClass, string $message): void
    {
        Log::channel($this->channel())->error('health_aggregate.job_failed', [
            'user_id' => $userId,
            'local_date' => $localDate,
            'exception_class' => $exceptionClass,
            'message' => $message,
        ]);
    }

    public function userDayCompleted(int $userId, string $localDate, int $upserted): void
    {
        Log::channel($this->channel())->info('health_aggregate.user_day_completed', [
            'user_id' => $userId,
            'local_date' => $localDate,
            'upserted' => $upserted,
        ]);
    }

    private function channel(): string
    {
        return config()->has('logging.channels.health_aggregate') ? 'health_aggregate' : 'stack';
    }
}
