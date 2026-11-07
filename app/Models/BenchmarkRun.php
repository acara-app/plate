<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Benchmark\HarnessReport;
use App\Enums\Benchmark\AnalysisPath;
use Carbon\CarbonInterface;
use Database\Factories\BenchmarkRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $analyzer_version
 * @property bool $smoke
 * @property int $repeats
 * @property int $meal_count
 * @property int $skipped_meals
 * @property array<string, mixed> $report
 * @property CarbonInterface $created_at
 */
final class BenchmarkRun extends Model
{
    /** @use HasFactory<BenchmarkRunFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function latestComparable(bool $smoke): ?self
    {
        return self::query()->where('smoke', $smoke)->latest('id')->first();
    }

    public function toHarnessReport(): HarnessReport
    {
        $report = $this->report;
        $paths = is_array($report['paths'] ?? null) ? $report['paths'] : [];
        $report['paths'] = array_values(array_filter(
            $paths,
            fn (mixed $path): bool => is_array($path) && is_string($path['path'] ?? null) && AnalysisPath::tryFrom($path['path']) instanceof AnalysisPath,
        ));

        return HarnessReport::from($report);
    }

    public function casts(): array
    {
        return [
            'smoke' => 'boolean',
            'repeats' => 'integer',
            'meal_count' => 'integer',
            'skipped_meals' => 'integer',
            'report' => 'array',
        ];
    }
}
