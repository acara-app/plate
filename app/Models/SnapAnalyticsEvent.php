<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapAnalyticsEventFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_key
 * @property string $name
 * @property array<string, string> $properties
 * @property CarbonImmutable $created_at
 */
#[WithoutTimestamps]
final class SnapAnalyticsEvent extends Model
{
    /** @use HasFactory<SnapAnalyticsEventFactory> */
    use HasFactory;

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
