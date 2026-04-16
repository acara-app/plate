<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
use App\Data\Memory\MemorySearchResultData;
use App\Enums\MemoryType;
use App\Models\Memory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ContextRetriever
{
    public function __construct(
        private EmbeddingService $embedder,
        private VectorStoreService $vectorStore,
        private GeneratesMemoryQueries $queryAgent,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $conversationTail
     * @return Collection<int, MemorySearchResultData>
     */
    public function recall(int $userId, string $userMessage, array $conversationTail = []): Collection
    {
        if ($userId <= 0) {
            return collect();
        }

        try {
            $context = $this->buildContext($conversationTail, $userMessage);
            $queries = $this->generateQueries($context, $userMessage);

            if ($queries === []) {
                return collect();
            }

            $embeddings = $this->embedder->generateBatch($queries);

            /** @var array<string, array{memory: Memory, score: float}> $candidates */
            $candidates = [];

            /** @phpstan-ignore cast.double */
            $threshold = (float) config('memory.retrieval.similarity_threshold', 0.38);

            foreach (array_keys($queries) as $index) {
                $vector = $embeddings[$index] ?? null;
                if ($vector === null) {
                    continue;
                }

                if ($vector === []) {
                    continue;
                }

                $hits = $this->vectorStore->search(
                    queryVector: $vector,
                    userId: $userId,
                    limit: 10,
                    minRelevance: $threshold,
                    filter: [],
                    includeArchived: false,
                );

                foreach ($hits as $hit) {
                    $id = $hit['memory']->id;
                    if (! isset($candidates[$id]) || $candidates[$id]['score'] < $hit['score']) {
                        $candidates[$id] = $hit;
                    }
                }
            }

            if ($candidates === []) {
                return collect();
            }

            /** @phpstan-ignore cast.int */
            $maxResults = (int) config('memory.retrieval.max_results', 7);

            $ranked = collect($candidates)
                ->map(fn (array $row): array => [
                    'memory' => $row['memory'],
                    'semantic' => $row['score'],
                    'score' => $this->compositeScore($row['memory'], $row['score']),
                ])
                ->sortByDesc('score')
                ->take($maxResults)
                ->values();

            $ranked->each(static fn (array $row) => $row['memory']->recordAccess());

            return $ranked->map(static fn (array $row): MemorySearchResultData => $row['memory']->toSearchResultData($row['semantic']));
        } catch (Throwable $throwable) {
            Log::warning('Memory context recall failed, returning empty set', [
                'user_id' => $userId,
                'error' => $throwable->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $conversationTail
     */
    private function buildContext(array $conversationTail, string $userMessage): string
    {
        /** @phpstan-ignore cast.int */
        $turns = (int) config('memory.retrieval.context_turns', 20);
        $tail = array_slice($conversationTail, -$turns);

        $lines = array_map(
            static fn (array $turn): string => sprintf('%s: %s', $turn['role'] ?? 'user', $turn['content'] ?? ''),
            $tail,
        );

        $lines[] = 'user: '.$userMessage;

        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    private function generateQueries(string $context, string $userMessage): array
    {
        try {
            $queries = $this->queryAgent->generateQueries($context);

            if ($queries !== []) {
                return $queries;
            }
        } catch (Throwable $throwable) {
            Log::debug('Query generation failed, falling back to user message', [
                'error' => $throwable->getMessage(),
            ]);
        }

        $trimmed = mb_trim($userMessage);

        return $trimmed === '' ? [] : [$trimmed];
    }

    private function compositeScore(Memory $memory, float $semantic): float
    {
        /** @var array{semantic: float, recency: float, frequency: float} $weights */
        $weights = (array) config('memory.retrieval.weights', ['semantic' => 0.60, 'recency' => 0.25, 'frequency' => 0.15]);
        /** @phpstan-ignore cast.int */
        $halfLife = (int) config('memory.retrieval.recency_half_life_days', 90);

        $lastAccess = $memory->last_accessed_at ?? $memory->created_at;
        $daysSince = $lastAccess === null ? $halfLife : $lastAccess->diffInDays(now());
        $recency = max(0.0, 1.0 - ($daysSince / max(1, $halfLife)));

        $frequency = min(1.0, $memory->access_count / 10);

        $typeBonus = match ($memory->memory_type) {
            MemoryType::Relationship => 0.10,
            MemoryType::Preference, MemoryType::Goal => 0.05,
            MemoryType::Event => $memory->created_at->diffInDays(now()) <= 7 ? 0.10 : 0.0,
            default => 0.0,
        };

        return ($semantic * (float) ($weights['semantic'] ?? 0.60))
            + ($recency * (float) ($weights['recency'] ?? 0.25))
            + ($frequency * (float) ($weights['frequency'] ?? 0.15))
            + $typeBonus;
    }
}
