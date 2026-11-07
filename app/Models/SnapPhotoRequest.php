<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapPhotoRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $period_id
 * @property string $subject
 * @property string $request_key
 * @property string $fingerprint
 * @property string|null $guest_id
 * @property string $status
 * @property string $attempt
 * @property string $source
 * @property string|null $result
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $expires_at
 */
#[Hidden(['result', 'guest_id'])]
#[WithoutTimestamps]
final class SnapPhotoRequest extends Model
{
    /** @use HasFactory<SnapPhotoRequestFactory> */
    use HasFactory;

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'period_id' => 'integer',
            'result' => 'encrypted',
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
