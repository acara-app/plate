<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $source_memory_id
 * @property string $target_memory_id
 * @property string $relationship
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Memory $source
 * @property-read Memory $target
 */
final class MemoryLink extends Model
{
    use HasFactory;
    use HasUlids;

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @return BelongsTo<Memory, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Memory::class, 'source_memory_id');
    }

    /**
     * @return BelongsTo<Memory, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Memory::class, 'target_memory_id');
    }
}
