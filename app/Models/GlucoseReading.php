<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReadingType;
use Carbon\CarbonInterface;
use Database\Factories\GlucoseReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read float $reading_value
 * @property-read ReadingType $reading_type
 * @property-read CarbonInterface $measured_at
 * @property-read string|null $notes
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 */
final class GlucoseReading extends Model
{
    /**
     * @use HasFactory<GlucoseReadingFactory>
     */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'reading_value' => 'float',
            'reading_type' => ReadingType::class,
            'measured_at' => 'datetime',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
