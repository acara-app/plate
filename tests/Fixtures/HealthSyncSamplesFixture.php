<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\HealthEntrySource;
use App\Models\MobileSyncDevice;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class HealthSyncSamplesFixture
{
    private const string CSV_PATH = __DIR__.'/health_sync_samples-003.csv';

    public static function load(User $user, ?MobileSyncDevice $device = null): int
    {
        $handle = fopen(self::CSV_PATH, 'rb');

        throw_if($handle === false, RuntimeException::class, 'Cannot open health_sync_samples fixture at '.self::CSV_PATH);

        try {
            $header = fgetcsv($handle, escape: '\\');

            throw_if($header === false, RuntimeException::class, 'Empty health_sync_samples fixture');

            /** @var array<string, int> $columnIndex */
            $columnIndex = array_flip($header);

            $batch = [];
            $inserted = 0;
            $deviceId = $device?->id;

            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                $metadata = self::nullable($row[$columnIndex['metadata']]);
                $batch[] = [
                    'user_id' => $user->id,
                    'mobile_sync_device_id' => $deviceId,
                    'type_identifier' => $row[$columnIndex['type_identifier']],
                    'value' => (float) $row[$columnIndex['value']],
                    'unit' => self::nullable($row[$columnIndex['unit']]) ?? '',
                    'measured_at' => $row[$columnIndex['measured_at']],
                    'source' => self::nullable($row[$columnIndex['source']]),
                    'metadata' => $metadata,
                    'timezone' => self::nullable($row[$columnIndex['timezone']]),
                    'entry_source' => HealthEntrySource::MobileSync->value,
                    'notes' => self::nullable($row[$columnIndex['notes']]),
                    'group_id' => self::nullable($row[$columnIndex['group_id']]),
                    'created_at' => $row[$columnIndex['created_at']],
                    'updated_at' => $row[$columnIndex['updated_at']],
                ];

                if (count($batch) >= 500) {
                    DB::table('health_sync_samples')->insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                DB::table('health_sync_samples')->insert($batch);
                $inserted += count($batch);
            }

            return $inserted;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<string, int>
     */
    public static function typeIdentifierCounts(): array
    {
        $handle = fopen(self::CSV_PATH, 'rb');

        throw_if($handle === false, RuntimeException::class, 'Cannot open health_sync_samples fixture');

        try {
            $header = fgetcsv($handle, escape: '\\');
            if ($header === false) {
                return [];
            }

            $typeIndex = array_flip($header)['type_identifier'];

            $counts = [];

            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                $type = $row[$typeIndex];
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }

            arsort($counts);

            return $counts;
        } finally {
            fclose($handle);
        }
    }

    public static function cumulativeGroundTruth(string $typeIdentifier, string $localDate, string $timezone = 'America/Regina'): float
    {
        $handle = fopen(self::CSV_PATH, 'rb');

        throw_if($handle === false, RuntimeException::class, 'Cannot open health_sync_samples fixture');

        $priority = ['Apple Watch', 'iPhone', 'Bluetooth Device'];

        try {
            $header = fgetcsv($handle, escape: '\\');
            if ($header === false) {
                return 0.0;
            }

            $idx = array_flip($header);

            /** @var array<int, array{bucket: int, value: float, rank: int}> $samples */
            $samples = [];

            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                if ($row[$idx['type_identifier']] !== $typeIdentifier) {
                    continue;
                }

                $measuredAt = $row[$idx['measured_at']];
                $tz = self::nullable($row[$idx['timezone']]) ?? $timezone;

                $dt = new DateTimeImmutable($measuredAt, new DateTimeZone('UTC'));
                $local = $dt->setTimezone(new DateTimeZone($tz));

                if ($local->format('Y-m-d') !== $localDate) {
                    continue;
                }

                $source = self::nullable($row[$idx['source']]) ?? '';
                $rank = 99;
                foreach ($priority as $i => $prefSubstring) {
                    if (str_contains($source, $prefSubstring)) {
                        $rank = $i + 1;
                        break;
                    }
                }

                $bucket = (int) floor($dt->getTimestamp() / 300);

                $samples[] = [
                    'bucket' => $bucket,
                    'timestamp' => $dt->getTimestamp(),
                    'value' => (float) $row[$idx['value']],
                    'rank' => $rank,
                ];
            }

            $bestRankPerBucket = [];
            foreach ($samples as $s) {
                $b = $s['bucket'];
                if (! isset($bestRankPerBucket[$b]) || $s['rank'] < $bestRankPerBucket[$b]) {
                    $bestRankPerBucket[$b] = $s['rank'];
                }
            }

            $total = 0.0;
            foreach ($samples as $s) {
                if ($s['rank'] === $bestRankPerBucket[$s['bucket']]) {
                    $total += $s['value'];
                }
            }

            return $total;
        } finally {
            fclose($handle);
        }
    }

    private static function nullable(string $value): ?string
    {
        $trimmed = mb_trim($value);

        if ($trimmed === '' || mb_strtoupper($trimmed) === 'NULL') {
            return null;
        }

        return $trimmed;
    }
}
