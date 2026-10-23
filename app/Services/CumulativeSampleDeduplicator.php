<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HealthSyncSample;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class CumulativeSampleDeduplicator
{
    private const int BUCKET_SECONDS = 300;

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @param  list<string>  $priority
     * @return Collection<int, HealthSyncSample>
     */
    public function handle(Collection $samples, array $priority): Collection
    {
        $covered = [];
        $uncoveredShares = [];

        $samplesByRank = $samples
            ->groupBy(fn (HealthSyncSample $sample): int => $this->sourceRank($sample->source, $priority), preserveKeys: true)
            ->sortKeys();

        foreach ($samplesByRank as $rankSamples) {
            $spans = [];

            foreach ($rankSamples as $key => $sample) {
                [$start, $end] = $spans[$key] = $this->span($sample);
                $uncoveredShares[$key] = [$end - $start - $this->coveredSeconds($start, $end, $covered), $end - $start];
            }

            $covered = $this->merge([...$covered, ...array_values($spans)]);
        }

        return $samples
            ->filter(fn (HealthSyncSample $sample, int $key): bool => $uncoveredShares[$key][0] > 0)
            ->map(function (HealthSyncSample $sample, int $key) use ($uncoveredShares): HealthSyncSample {
                [$uncovered, $length] = $uncoveredShares[$key];

                if ($uncovered === $length) {
                    return $sample;
                }

                $share = clone $sample;
                $share->value = $sample->value * $uncovered / $length;

                return $share;
            })
            ->values();
    }

    /**
     * @return array{int, int}
     */
    private function span(HealthSyncSample $sample): array
    {
        $start = $sample->measured_at->getTimestamp();
        $end = $sample->ended_at?->getTimestamp() ?? $start;

        if ($end > $start) {
            return [$start, $end];
        }

        $bucketStart = intdiv($start, self::BUCKET_SECONDS) * self::BUCKET_SECONDS;

        return [$bucketStart, $bucketStart + self::BUCKET_SECONDS];
    }

    /**
     * @param  list<array{int, int}>  $covered
     */
    private function coveredSeconds(int $start, int $end, array $covered): int
    {
        $seconds = 0;

        foreach ($covered as [$coveredStart, $coveredEnd]) {
            if ($coveredStart >= $end) {
                break;
            }

            $seconds += max(0, min($end, $coveredEnd) - max($start, $coveredStart));
        }

        return $seconds;
    }

    /**
     * @param  list<array{int, int}>  $spans
     * @return list<array{int, int}>
     */
    private function merge(array $spans): array
    {
        usort($spans, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($spans as [$start, $end]) {
            $last = array_key_last($merged);

            if ($last !== null && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);

                continue;
            }

            $merged[] = [$start, $end];
        }

        return $merged;
    }

    /**
     * @param  list<string>  $priority
     */
    private function sourceRank(?string $source, array $priority): int
    {
        // @codeCoverageIgnoreStart
        if ($source === null || $source === '') {
            return PHP_INT_MAX;
        }

        // @codeCoverageIgnoreEnd

        $source = Str::squish($source);

        foreach ($priority as $i => $preferredSubstring) {
            if (str_contains($source, $preferredSubstring)) {
                return $i + 1;
            }
        }

        // @codeCoverageIgnoreStart
        return PHP_INT_MAX;
        // @codeCoverageIgnoreEnd
    }
}
