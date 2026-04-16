<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Contracts\Ai\Memory\ConsolidateMemoriesTool;
use App\Models\Memory;
use App\Services\Memory\EmbeddingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ConsolidateMemories implements ConsolidateMemoriesTool
{
    public function __construct(private EmbeddingService $embedder) {}

    /**
     * @param  array<string>  $memoryIds
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, string>|null  $categories
     */
    public function execute(
        array $memoryIds,
        string $synthesizedContent,
        ?array $metadata = null,
        ?int $importance = null,
        bool $deleteOriginals = true,
        ?array $categories = null,
    ): string {
        if (count($memoryIds) < 2) {
            throw MemoryStorageException::consolidationFailed($memoryIds, 'At least 2 memory IDs are required.');
        }

        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->whereIn('id', $memoryIds);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $sources = $query->get()->keyBy('id');

        foreach ($memoryIds as $memoryId) {
            throw_unless($sources->has($memoryId), MemoryNotFoundException::class, $memoryId);
        }

        /** @phpstan-ignore cast.int */
        $maxGeneration = (int) config('memory.consolidation.max_generation', 5);
        $maxSourceGeneration = (int) $sources->max('consolidation_generation');

        if ($maxSourceGeneration >= $maxGeneration) {
            throw MemoryStorageException::consolidationFailed(
                $memoryIds,
                sprintf('Max consolidation generation %d reached.', $maxGeneration),
            );
        }

        try {
            return DB::transaction(function () use ($sources, $memoryIds, $synthesizedContent, $metadata, $importance, $deleteOriginals, $userId, $maxSourceGeneration, $categories): string {
                $mergedMetadata = $metadata ?? $this->mergeMetadata($sources->all());
                $mergedCategories = $categories !== null
                    ? $this->normalizeCategories($categories)
                    : $this->mergeCategories($sources->all());
                $mergedImportance = $this->resolveImportance($sources->all(), $importance);
                $targetUserId = $userId > 0 ? $userId : (int) $sources->first()->user_id;

                $consolidated = new Memory;
                $consolidated->forceFill([
                    'user_id' => $targetUserId,
                    'content' => $synthesizedContent,
                    'metadata' => $mergedMetadata,
                    'categories' => $mergedCategories,
                    'importance' => $mergedImportance,
                    'source' => 'consolidation',
                    'consolidated_from' => array_values($memoryIds),
                    'consolidation_generation' => $maxSourceGeneration + 1,
                ]);
                $consolidated->setEmbedding($this->embedder->generate($synthesizedContent));
                $consolidated->save();

                foreach ($sources as $sourceMemory) {
                    $sourceMemory->forceFill([
                        'consolidated_into' => $consolidated->id,
                        'consolidated_at' => now(),
                    ])->save();

                    if ($deleteOriginals) {
                        $sourceMemory->delete();
                    }
                }

                return $consolidated->id;
            });
        } catch (Throwable $throwable) {
            throw_if($throwable instanceof MemoryNotFoundException || $throwable instanceof MemoryStorageException, $throwable);

            throw MemoryStorageException::consolidationFailed($memoryIds, $throwable->getMessage());
        }
    }

    /**
     * @param  array<int|string, Memory>  $memories
     * @return array<string, mixed>
     */
    private function mergeMetadata(array $memories): array
    {
        $merged = [];
        foreach ($memories as $memory) {
            $merged = array_merge($merged, $memory->metadata ?? []);
        }

        return $merged;
    }

    /**
     * @param  array<int|string, Memory>  $memories
     * @return array<int, string>
     */
    private function mergeCategories(array $memories): array
    {
        $all = [];
        foreach ($memories as $memory) {
            foreach ($memory->categories ?? [] as $category) {
                $all[] = $category;
            }
        }

        return array_values(array_unique($all));
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, string>
     */
    private function normalizeCategories(array $raw): array
    {
        $filtered = array_values(array_filter(
            $raw,
            static fn (mixed $c): bool => is_string($c) && mb_trim($c) !== '',
        ));

        return array_values(array_unique($filtered));
    }

    /**
     * @param  array<int|string, Memory>  $memories
     */
    private function resolveImportance(array $memories, ?int $explicitImportance): int
    {
        /** @phpstan-ignore cast.int */
        $min = (int) config('memory.importance.min', 1);
        /** @phpstan-ignore cast.int */
        $max = (int) config('memory.importance.max', 10);

        if ($explicitImportance !== null) {
            return max($min, min($max, $explicitImportance));
        }

        $values = array_map(static fn (Memory $m): int => $m->importance, $memories);

        return max($min, min($max, $values === [] ? $min : max($values)));
    }
}
