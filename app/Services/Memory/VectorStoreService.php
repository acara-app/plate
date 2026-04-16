<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Models\Memory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class VectorStoreService
{
    public function __construct(private MemoryFilterValidator $filterValidator) {}

    /**
     * @param  array<int, float>  $queryVector
     * @param  array<string, mixed>  $filter
     * @return array<int, array{memory: Memory, score: float}>
     */
    public function search(
        array $queryVector,
        int $userId,
        int $limit,
        float $minRelevance,
        array $filter,
        bool $includeArchived,
    ): array {
        $this->filterValidator->validate($filter);

        if ($this->isPostgres()) {
            return $this->searchPgVector($queryVector, $userId, $limit, $minRelevance, $filter, $includeArchived);
        }

        return $this->searchFallback($queryVector, $userId, $limit, $minRelevance, $filter, $includeArchived);
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === [] || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        foreach ($a as $i => $valA) {
            $valB = $b[$i];
            $dot += $valA * $valB;
            $magA += $valA * $valA;
            $magB += $valB * $valB;
        }

        if ($magA === 0.0 || $magB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($magA) * sqrt($magB));
    }

    public function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * @param  array<int, float>  $queryVector
     * @param  array<string, mixed>  $filter
     * @return array<int, array{memory: Memory, score: float}>
     */
    private function searchPgVector(
        array $queryVector,
        int $userId,
        int $limit,
        float $minRelevance,
        array $filter,
        bool $includeArchived,
    ): array {
        $embedding = '['.implode(',', $queryVector).']';

        $query = Memory::query()
            ->selectRaw('memories.*, 1 - (embedding <=> ?) as similarity', [$embedding])
            ->whereRaw('1 - (embedding <=> ?) >= ?', [$embedding, $minRelevance])
            ->where('user_id', $userId)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        $this->applyFilter($query, $filter);

        $memories = $query->orderByDesc('similarity')->limit($limit)->get();

        return $memories
            ->map(static fn (Memory $memory): array => [
                'memory' => $memory,
                /** @phpstan-ignore cast.double */
                'score' => (float) $memory->getAttribute('similarity'),
            ])
            ->all();
    }

    /**
     * @param  array<int, float>  $queryVector
     * @param  array<string, mixed>  $filter
     * @return array<int, array{memory: Memory, score: float}>
     */
    private function searchFallback(
        array $queryVector,
        int $userId,
        int $limit,
        float $minRelevance,
        array $filter,
        bool $includeArchived,
    ): array {
        $query = Memory::query()
            ->where('user_id', $userId)
            ->whereNotNull('embedding')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        $this->applyFilter($query, $filter);

        $candidates = $query->get();

        $scored = $candidates
            ->map(fn (Memory $memory): array => [
                'memory' => $memory,
                'score' => $this->cosineSimilarity($queryVector, $memory->getEmbeddingArray()),
            ])
            ->filter(static fn (array $row): bool => $row['score'] >= $minRelevance)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $scored->all();
    }

    /**
     * @param  Builder<Memory>  $query
     * @param  array<string, mixed>  $filter
     */
    private function applyFilter(Builder $query, array $filter): void
    {
        if (isset($filter['category']) && is_string($filter['category'])) {
            $query->whereJsonContains('categories', $filter['category']);
        }

        if (isset($filter['categories']) && is_array($filter['categories'])) {
            $query->where(function (Builder $q) use ($filter): void {
                foreach ($filter['categories'] as $category) {
                    $q->orWhereJsonContains('categories', $category);
                }
            });
        }

        if (isset($filter['source']) && is_string($filter['source'])) {
            $query->where('source', $filter['source']);
        }

        if (isset($filter['importance_min'])) {
            $query->where('importance', '>=', (int) $filter['importance_min']);
        }

        if (isset($filter['importance_max'])) {
            $query->where('importance', '<=', (int) $filter['importance_max']);
        }

        if (isset($filter['user_id'])) {
            $query->where('user_id', (int) $filter['user_id']);
        }

        if (array_key_exists('is_archived', $filter)) {
            $query->where('is_archived', (bool) $filter['is_archived']);
        }

        if (isset($filter['tags']) && is_array($filter['tags'])) {
            $query->where(function (Builder $q) use ($filter): void {
                foreach ($filter['tags'] as $tag) {
                    $q->orWhereJsonContains('metadata->tags', $tag);
                }
            });
        }
    }
}
