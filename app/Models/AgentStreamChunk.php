<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $run_id
 * @property int $sequence
 * @property string $type
 * @property array<string, mixed> $payload
 * @property array<string, mixed>|null $vercel
 * @property CarbonInterface $created_at
 * @property-read AgentStreamRun $run
 */
#[WithoutTimestamps]
final class AgentStreamChunk extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'payload' => 'array',
            'vercel' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AgentStreamRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentStreamRun::class, 'run_id');
    }
}
