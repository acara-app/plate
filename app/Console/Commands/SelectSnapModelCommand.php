<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\PathMetrics;
use App\Models\BenchmarkRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('snap:select-model {baseline : Baseline benchmark run ID} {candidates* : Candidate benchmark run IDs}')]
#[Description('Rank measured photo model candidates against accuracy, reliability, latency, and cost gates')]
final class SelectSnapModelCommand extends Command
{
    public function handle(): int
    {
        $baseline = BenchmarkRun::query()->findOrFail($this->argument('baseline'))->toHarnessReport();
        $qualified = [];
        foreach ($this->argument('candidates') as $id) {
            $report = BenchmarkRun::query()->findOrFail($id)->toHarnessReport();
            if (! $this->qualifies($report, $baseline)) {
                $this->warn('Run '.$id.' failed the release gates.');

                continue;
            }

            $path = $report->paths[0];
            $qualified[] = ['run' => $id, 'report' => $report, 'carbs' => $path->metrics->carbs->mae, 'cost' => $path->costPerHundred(), 'latency' => $path->p95LatencyMs];
        }

        usort($qualified, fn (array $a, array $b): int => [$a['carbs'], $a['cost'], $a['latency']] <=> [$b['carbs'], $b['cost'], $b['latency']]);
        if ($qualified === []) {
            $this->error('No candidate qualifies. Keep using the current photo model.');

            return self::FAILURE;
        }

        $winner = $qualified[0];
        $report = $winner['report'];
        $this->info('Selected benchmark run '.$winner['run'].'. Review the dataset and provider billing before switching the photo analyzer to this model:');
        $this->line(Str::of($report->analyzerVersion)->replaceMatches('#/p\d+$#', '')->toString());
        $this->line(sprintf('Measured cost per 100 successful scans: $%.4f', $winner['cost']));

        return self::SUCCESS;
    }

    private function qualifies(HarnessReport $report, HarnessReport $baseline): bool
    {
        if ($report->datasetHash === null || $report->datasetHash !== $baseline->datasetHash
            || $report->skippedMeals !== 0 || $baseline->skippedMeals !== 0
            || $report->repeats < 3 || $baseline->repeats < 3
            || $report->provider === null || $report->maxTokens === null
            || $report->paths->count() !== 1 || $baseline->paths->count() !== 1) {
            return false;
        }

        foreach ($report->paths as $index => $path) {
            $reference = $baseline->paths[$index];
            if (! $this->pathQualifies($path, $reference)) {
                return false;
            }
        }

        return true;
    }

    private function pathQualifies(PathMetrics $path, PathMetrics $baseline): bool
    {
        $metrics = $path->metrics;
        $reference = $baseline->metrics;

        return $metrics->mealCount >= 30 && $metrics->mealCount === $reference->mealCount
            && $path->costPerHundred() !== null && $path->costPerHundred() <= 3.0
            && $path->successRate() >= 0.98 && $path->p95LatencyMs !== null && $path->p95LatencyMs < 30_000
            && $metrics->carbs->mae !== null && $reference->carbs->mae !== null && $metrics->carbs->mae <= $reference->carbs->mae
            && $metrics->calories->mae !== null && $reference->calories->mae !== null && $metrics->calories->mae <= $reference->calories->mae
            && $metrics->itemization->recall !== null && $reference->itemization->recall !== null && $metrics->itemization->recall >= $reference->itemization->recall;
    }
}
