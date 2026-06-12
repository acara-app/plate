<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\PathMetrics;
use App\Enums\Benchmark\AnalysisPath;
use App\Models\BenchmarkRun;
use App\Services\Benchmark\MetricsCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\LaravelData\DataCollection;

/**
 * @extends Factory<BenchmarkRun>
 */
final class BenchmarkRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metrics = (new MetricsCalculator)->calculate([]);

        $report = new HarnessReport(
            analyzerVersion: 'gemini-3.5-flash/p3',
            referenceLookupEnabled: true,
            repeats: 5,
            skippedMeals: 0,
            paths: new DataCollection(PathMetrics::class, [
                new PathMetrics(path: AnalysisPath::Raw, failedRuns: 0, metrics: $metrics),
                new PathMetrics(path: AnalysisPath::Enriched, failedRuns: 0, metrics: $metrics),
            ]),
        );

        return [
            'analyzer_version' => 'gemini-3.5-flash/p3',
            'reference_lookup_enabled' => true,
            'smoke' => false,
            'repeats' => 5,
            'meal_count' => 0,
            'skipped_meals' => 0,
            'report' => $report->toArray(),
        ];
    }
}
