<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class GuestScanReportData extends Data
{
    /**
     * @param  array<int, int>  $guestsByScans
     */
    public function __construct(
        public string $from,
        public string $to,
        public int $guests,
        public int $scans,
        public int $failedScans,
        public array $guestsByScans,
        public int $cameBack,
        public int $signedIn,
        public int $retentionDays,
    ) {}
}
