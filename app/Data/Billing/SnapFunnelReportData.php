<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class SnapFunnelReportData extends Data
{
    /**
     * @param  array<string, list<array{stage: string, label: string, journeys: int}>>  $paths
     * @param  array<string, array{journeys: int, paths: array<string, list<array{stage: string, label: string, journeys: int}>>}>  $segments
     * @param  list<array{label: string, journeys: int}>  $problems
     * @param  array<string, int>  $rawEvents
     * @param  array{live: int, attributed: int, staff: int, unattributed: int, test_mode: int, subscriptions: int, subscriptions_paid: int, subscriptions_unpaid: array<string, int>}  $payments
     * @param  list<string>  $notes
     */
    public function __construct(
        public string $from,
        public string $to,
        public int $windowDays,
        public bool $includesInternal,
        public int $journeys,
        public int $openJourneys,
        public ?string $ledgerStartedAt,
        public array $paths,
        public ?string $segmentBy,
        public array $segments,
        public array $problems,
        public array $rawEvents,
        public array $payments,
        public array $notes,
    ) {}
}
