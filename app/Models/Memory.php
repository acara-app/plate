<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Memory\MemoryData;
use App\Data\Memory\MemorySearchResultData;
use App\Data\Memory\RelatedMemoryData;
use App\Enums\MemoryType;
use Carbon\CarbonInterface;
use Database\Factories\MemoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string $content
 * @property array<string, mixed>|null $metadata
 * @property array<int, string>|null $categories
 * @property MemoryType|null $memory_type
 * @property int $importance
 * @property string|null $source
 * @property bool $is_archived
 * @property bool $is_pinned
 * @property CarbonInterface|null $expires_at
 * @property int $access_count
 * @property CarbonInterface|null $last_accessed_at
 * @property array<int, string>|null $consolidated_from
 * @property string|null $consolidated_into
 * @property CarbonInterface|null $consolidated_at
 * @property int $consolidation_generation
 * @property string|null $embedding
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read User $user
 * @property-read Collection<int, MemoryLink> $outgoingLinks
 * @property-read Collection<int, MemoryLink> $incomingLinks
 */
final class Memory extends Model
{
    /** @use HasFactory<MemoryFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'memories';

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
     * @return HasMany<MemoryLink, $this>
     */
    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(MemoryLink::class, 'source_memory_id');
    }

    /**
     * @return HasMany<MemoryLink, $this>
     */
    public function incomingLinks(): HasMany
    {
        return $this->hasMany(MemoryLink::class, 'target_memory_id');
    }

    public function recordAccess(): void
    {
        $this->forceFill([
            'access_count' => $this->access_count + 1,
            'last_accessed_at' => now(),
        ])->saveQuietly();
    }

    /**
     * @param  array<int, float>  $vector
     */
    public function setEmbedding(array $vector): void
    {
        $this->embedding = '['.implode(',', $vector).']';
    }

    /**
     * @return array<int, float>
     */
    public function getEmbeddingArray(): array
    {
        if (! is_string($this->embedding) || $this->embedding === '') {
            return [];
        }

        $cleaned = mb_trim($this->embedding, '[]');

        if ($cleaned === '') {
            return [];
        }

        return array_map(static fn (string $v): float => (float) $v, explode(',', $cleaned));
    }

    public function toMemoryData(): MemoryData
    {
        return new MemoryData(
            id: $this->id,
            content: $this->content,
            metadata: $this->metadata ?? [],
            importance: $this->importance,
            categories: $this->categories ?? [],
            createdAt: $this->created_at->toIso8601String(),
            updatedAt: $this->updated_at->toIso8601String(),
            expiresAt: $this->expires_at?->toIso8601String(),
            isArchived: $this->is_archived,
        );
    }

    public function toSearchResultData(float $score): MemorySearchResultData
    {
        return new MemorySearchResultData(
            id: $this->id,
            content: $this->content,
            score: $score,
            metadata: $this->metadata ?? [],
            importance: $this->importance,
            categories: $this->categories ?? [],
        );
    }

    public function toRelatedData(string $relationship, int $depth): RelatedMemoryData
    {
        return new RelatedMemoryData(
            id: $this->id,
            content: $this->content,
            relationship: $relationship,
            depth: $depth,
            metadata: $this->metadata ?? [],
        );
    }

    /**
     * @param  Builder<Memory>  $query
     */
    #[Scope]
    protected function forUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<Memory>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_archived', false)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @param  Builder<Memory>  $query
     */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->where('is_archived', true);
    }

    /**
     * @param  Builder<Memory>  $query
     */
    #[Scope]
    protected function pinned(Builder $query): void
    {
        $query->where('is_pinned', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'categories' => 'array',
            'memory_type' => MemoryType::class,
            'consolidated_from' => 'array',
            'importance' => 'integer',
            'access_count' => 'integer',
            'consolidation_generation' => 'integer',
            'is_archived' => 'boolean',
            'is_pinned' => 'boolean',
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'consolidated_at' => 'datetime',
        ];
    }
}
