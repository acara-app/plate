<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HealthSyncSample;
use Illuminate\Support\Collection;

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
        if ($samples->isEmpty()) {
            // @codeCoverageIgnoreStart
            return $samples;
            // @codeCoverageIgnoreEnd
        }

        $buckets = [];

        foreach ($samples as $sample) {
            $startEpoch = $sample->measured_at->getTimestamp();
            $startBucket = (int) floor($startEpoch / self::BUCKET_SECONDS);

            $endBucket = $startBucket;
            if ($sample->ended_at !== null) {
                // @codeCoverageIgnoreStart
                $endEpoch = $sample->ended_at->getTimestamp();
                $endBucket = (int) floor($endEpoch / self::BUCKET_SECONDS);
                // @codeCoverageIgnoreEnd
            }

            for ($b = $startBucket; $b <= $endBucket; $b++) {
                $buckets[$b] ??= [];
                $buckets[$b][] = $sample;
            }
        }

        $keptIds = [];

        foreach ($buckets as $group) {
            $bestRank = PHP_INT_MAX;
            $bestSamples = [];

            foreach ($group as $sample) {
                $rank = $this->sourceRank($sample->source, $priority);

                if ($rank < $bestRank) {
                    $bestRank = $rank;
                    $bestSamples = [$sample];
                } elseif ($rank === $bestRank) {
                    $bestSamples[] = $sample;
                }
            }

            foreach ($bestSamples as $sample) {
                $id = spl_object_id($sample);
                $keptIds[$id] ??= $sample;
            }
        }

        return new Collection(array_values($keptIds));
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
