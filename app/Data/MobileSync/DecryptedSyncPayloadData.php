<?php

declare(strict_types=1);

namespace App\Data\MobileSync;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class DecryptedSyncPayloadData extends Data
{
    public function __construct(
        /** @var list<HealthEntryData> */
        #[DataCollectionOf(HealthEntryData::class)]
        public array $entries,
        /** @var list<SleepEventData> */
        #[DataCollectionOf(SleepEventData::class)]
        public array $sleep_events = [],
    ) {}
}
