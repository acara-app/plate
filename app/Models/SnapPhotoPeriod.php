<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapPhotoPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $subject
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property int $allowance
 * @property string $mode
 */
#[WithoutTimestamps]
final class SnapPhotoPeriod extends Model
{
    /** @use HasFactory<SnapPhotoPeriodFactory> */
    use HasFactory;

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'allowance' => 'integer',
        ];
    }
}
