<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property CarbonInterface|null $last_extracted_at
 * @property string|null $last_extracted_message_id
 * @property CarbonInterface|null $last_extracted_message_at
 * @property int $extracted_count
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 */
final class MemoryExtractionCheckpoint extends Model
{
    use HasFactory;

    protected $table = 'memory_extraction_checkpoints';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_extracted_at' => 'datetime',
            'last_extracted_message_at' => 'datetime',
            'extracted_count' => 'integer',
        ];
    }
}
