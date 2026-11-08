<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SnapGuestDailyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $date
 * @property int $guests
 * @property int $scans
 * @property int $failed_scans
 * @property array<int, int> $guests_by_scans
 * @property int $came_back
 * @property int $signed_in
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class SnapGuestDailyStat extends Model
{
    /** @use HasFactory<SnapGuestDailyStatFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'guests' => 'integer',
            'scans' => 'integer',
            'failed_scans' => 'integer',
            'guests_by_scans' => 'array',
            'came_back' => 'integer',
            'signed_in' => 'integer',
        ];
    }
}
