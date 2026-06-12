<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Benchmark\HarnessReport;
use Database\Factories\BenchmarkRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $analyzer_version
 * @property bool $reference_lookup_enabled
 * @property bool $smoke
 * @property int $repeats
 * @property int $meal_count
 * @property int $skipped_meals
 * @property array<string, mixed> $report
 * @property \Carbon\CarbonInterface $created_at
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
        return HarnessReport::from($this->report);
    }

    public function casts(): array
    {
        return [
            'reference_lookup_enabled' => 'boolean',
            'smoke' => 'boolean',
            'repeats' => 'integer',
            'meal_count' => 'integer',
            'skipped_meals' => 'integer',
            'report' => 'array',
        ];
    }
}
