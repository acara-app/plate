<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Ai\Facades\Memory as MemoryFacade;
use App\Contracts\Ai\Memory\DecidesMemoryMerge;
use App\Data\Memory\ConsolidationResultData;
use App\Data\Memory\MemoryClusterData;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class MemoryConsolidator
{
    public function __construct(
        private VectorStoreService $vectorStore,
        private DecidesMemoryMerge $decider,
    ) {}

    public function consolidateForUser(
        int $userId,
        bool $dryRun = false,
        ?int $daysLookback = null,
        ?float $threshold = null,
    ): ConsolidationResultData {
        if ($userId <= 0) {
            return ConsolidationResultData::none();
        }

        /** @phpstan-ignore cast.double */
        $threshold ??= (float) config('memory.consolidation.similarity_threshold', 0.80);
        /** @phpstan-ignore cast.int */
        $minClusterSize = (int) config('memory.consolidation.min_cluster_size', 2);

        $candidates = $this->candidatesFor($userId, $daysLookback);

        if ($candidates->count() < $minClusterSize) {
            return ConsolidationResultData::none();
        }

        $clusters = $this->buildClusters($candidates, $threshold);

        if ($clusters->isEmpty()) {
            return ConsolidationResultData::none();
        }

        return $this->processClusters($clusters, $candidates, $userId, $dryRun);
    }

    /**
     * @return Collection<int, MemoryClusterData>
     */
    public function buildClustersForUser(
        int $userId,
        ?int $daysLookback = null,
        ?float $threshold = null,
    ): Collection {
        /** @phpstan-ignore cast.double */
        $threshold ??= (float) config('memory.consolidation.similarity_threshold', 0.80);
        /** @phpstan-ignore cast.int */
        $minClusterSize = (int) config('memory.consolidation.min_cluster_size', 2);

        $candidates = $this->candidatesFor($userId, $daysLookback);

        if ($candidates->count() < $minClusterSize) {
            return collect();
        }

        return $this->buildClusters($candidates, $threshold);
    }

    /**
     * @return EloquentCollection<int, Memory>
     */
    public function candidatesFor(int $userId, ?int $daysLookback = null): EloquentCollection
    {
        /** @phpstan-ignore cast.int */
        $maxPerRun = (int) config('memory.consolidation.max_memories_per_run', 100);

        $query = Memory::query()
            ->where('user_id', $userId)
            ->whereNull('consolidated_into')
            ->whereNotNull('embedding')
            ->where('is_archived', false)
            ->where('is_pinned', false)->oldest();

        if ($daysLookback !== null) {
            $query->where('created_at', '>=', now()->subDays($daysLookback))
                ->limit($maxPerRun);
        }

        /** @var EloquentCollection<int, Memory> $result */
        $result = $query->get();

        return $result;
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return Collection<int, MemoryClusterData>
     */
    public function buildClusters(EloquentCollection $memories, float $threshold): Collection
    {
        if ($memories->count() < 2) {
            return collect();
        }

        $pairs = $this->computePairwiseSimilarities($memories, $threshold);

        if ($pairs === []) {
            return collect();
        }

        return $this->greedyCluster($pairs, $memories);
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return array<int, array{id1: string, id2: string, similarity: float}>
     */
    private function computePairwiseSimilarities(EloquentCollection $memories, float $threshold): array
    {
        if ($this->vectorStore->isPostgres()) {
            return $this->computePairwiseSimilaritiesPgVector($memories->pluck('id')->all(), $threshold);
        }

        return $this->computePairwiseSimilaritiesFallback($memories, $threshold);
    }

    /**
     * @param  array<int, string>  $memoryIds
     * @return array<int, array{id1: string, id2: string, similarity: float}>
     */
    private function computePairwiseSimilaritiesPgVector(array $memoryIds, float $threshold): array
    {
        if (count($memoryIds) < 2) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($memoryIds), '?'));

        $sql = sprintf(
            'SELECT m1.id AS id1, m2.id AS id2, 1 - (m1.embedding <=> m2.embedding) AS similarity
             FROM memories m1
             CROSS JOIN memories m2
             WHERE m1.id < m2.id
               AND m1.id IN (%s)
               AND m2.id IN (%s)
               AND 1 - (m1.embedding <=> m2.embedding) >= ?
             ORDER BY similarity DESC',
            $placeholders,
            $placeholders,
        );

        $bindings = [...$memoryIds, ...$memoryIds, $threshold];

        $rows = DB::select($sql, $bindings);

        return array_map(static fn (object $row): array => [
            'id1' => (string) $row->id1,
            'id2' => (string) $row->id2,
            'similarity' => (float) $row->similarity,
        ], $rows);
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return array<int, array{id1: string, id2: string, similarity: float}>
     */
    private function computePairwiseSimilaritiesFallback(EloquentCollection $memories, float $threshold): array
    {
        $all = $memories->all();
        $n = count($all);
        $pairs = [];

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                /** @var Memory $a */
                $a = $all[$i];
                /** @var Memory $b */
                $b = $all[$j];

                $sim = $this->vectorStore->cosineSimilarity($a->getEmbeddingArray(), $b->getEmbeddingArray());

                if ($sim >= $threshold) {
                    $pairs[] = [
                        'id1' => $a->id < $b->id ? $a->id : $b->id,
                        'id2' => $a->id < $b->id ? $b->id : $a->id,
                        'similarity' => $sim,
                    ];
                }
            }
        }

        usort($pairs, static fn (array $a, array $b): int => $b['similarity'] <=> $a['similarity']);

        return $pairs;
    }

    /**
     * @param  array<int, array{id1: string, id2: string, similarity: float}>  $pairs
     * @param  EloquentCollection<int, Memory>  $memories
     * @return Collection<int, MemoryClusterData>
     */
    private function greedyCluster(array $pairs, EloquentCollection $memories): Collection
    {
        /** @phpstan-ignore cast.int */
        $maxSize = (int) config('memory.consolidation.max_cluster_size', 5);
        /** @phpstan-ignore cast.int */
        $minSize = (int) config('memory.consolidation.min_cluster_size', 2);

        $clusters = [];
        $assigned = [];
        $clusterId = 0;

        foreach ($pairs as $pair) {
            $id1 = $pair['id1'];
            $id2 = $pair['id2'];

            if (isset($assigned[$id1]) && isset($assigned[$id2])) {
                continue;
            }

            $target = $assigned[$id1] ?? $assigned[$id2] ?? null;

            if ($target === null) {
                $clusters[$clusterId] = [
                    'memory_ids' => [$id1, $id2],
                    'pairs' => [$pair],
                ];
                $assigned[$id1] = $clusterId;
                $assigned[$id2] = $clusterId;
                $clusterId++;

                continue;
            }

            if (count($clusters[$target]['memory_ids']) >= $maxSize) {
                continue;
            }

            $newId = isset($assigned[$id1]) ? $id2 : $id1;

            if (! isset($assigned[$newId])) {
                $clusters[$target]['memory_ids'][] = $newId;
                $clusters[$target]['pairs'][] = $pair;
                $assigned[$newId] = $target;
            }
        }

        /** @var array<string, Memory> $byId */
        $byId = $memories->keyBy('id')->all();

        return collect($clusters)
            ->filter(static fn (array $c): bool => count($c['memory_ids']) >= $minSize)
            ->map(function (array $cluster, int $id) use ($byId): MemoryClusterData {
                $ids = $cluster['memory_ids'];
                /** @var array<int, float> $sims */
                $sims = array_column($cluster['pairs'], 'similarity');
                $avg = $sims === [] ? 0.0 : array_sum($sims) / count($sims);
                $maxGen = 0;
                foreach ($ids as $memoryId) {
                    $memory = $byId[$memoryId] ?? null;
                    if ($memory !== null && $memory->consolidation_generation > $maxGen) {
                        $maxGen = $memory->consolidation_generation;
                    }
                }

                return new MemoryClusterData(
                    id: $id,
                    memoryIds: array_values($ids),
                    similarities: array_values($cluster['pairs']),
                    averageSimilarity: $avg,
                    maxGeneration: $maxGen,
                );
            })
            ->values();
    }

    /**
     * @param  Collection<int, MemoryClusterData>  $clusters
     * @param  EloquentCollection<int, Memory>  $candidates
     */
    private function processClusters(
        Collection $clusters,
        EloquentCollection $candidates,
        int $userId,
        bool $dryRun,
    ): ConsolidationResultData {
        /** @phpstan-ignore cast.int */
        $maxGeneration = (int) config('memory.consolidation.max_generation', 5);

        /** @var array<string, Memory> $byId */
        $byId = $candidates->keyBy('id')->all();

        $clustersProcessed = 0;
        $memoriesConsolidated = 0;
        $memoriesCreated = 0;
        $clustersKeptSeparate = 0;
        $errors = 0;
        $details = [];

        foreach ($clusters as $cluster) {
            $clustersProcessed++;

            try {
                if ($cluster->maxGeneration >= $maxGeneration) {
                    $clustersKeptSeparate++;
                    $details[] = [
                        'cluster_id' => $cluster->id,
                        'memory_ids' => $cluster->memoryIds,
                        'should_merge' => false,
                        'reasoning' => sprintf('Skipped: max generation %d reached.', $maxGeneration),
                    ];

                    continue;
                }

                $decision = $this->decider->decide($this->buildDecisionPrompt($cluster, $byId));

                $details[] = [
                    'cluster_id' => $cluster->id,
                    'memory_ids' => $cluster->memoryIds,
                    'should_merge' => $decision['should_merge'],
                    'reasoning' => $decision['reasoning'],
                    'synthesized_content' => $decision['synthesized_content'],
                    'importance' => $decision['importance'],
                ];

                if (! $decision['should_merge'] || $decision['synthesized_content'] === null || $decision['synthesized_content'] === '') {
                    $clustersKeptSeparate++;

                    continue;
                }

                if ($dryRun) {
                    $memoriesConsolidated += $cluster->size();
                    $memoriesCreated++;

                    continue;
                }

                MemoryFacade::consolidate(
                    memoryIds: $cluster->memoryIds,
                    synthesizedContent: $decision['synthesized_content'],
                    metadata: [
                        'user_id' => $userId,
                        'source' => 'consolidation',
                        'average_similarity' => $cluster->averageSimilarity,
                        'reasoning' => $decision['reasoning'],
                    ],
                    importance: $decision['importance'],
                    categories: $decision['categories'] === [] ? null : $decision['categories'],
                );

                $memoriesConsolidated += $cluster->size();
                $memoriesCreated++;
            } catch (Throwable $e) {
                $errors++;
                Log::warning('Memory consolidation cluster failed', [
                    'user_id' => $userId,
                    'cluster_id' => $cluster->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new ConsolidationResultData(
            clustersProcessed: $clustersProcessed,
            memoriesConsolidated: $memoriesConsolidated,
            memoriesCreated: $memoriesCreated,
            clustersKeptSeparate: $clustersKeptSeparate,
            errors: $errors,
            details: $details,
        );
    }

    /**
     * @param  array<string, Memory>  $byId
     */
    private function buildDecisionPrompt(MemoryClusterData $cluster, array $byId): string
    {
        $lines = [];

        foreach ($cluster->memoryIds as $memoryId) {
            $memory = $byId[$memoryId] ?? null;
            if ($memory === null) {
                continue;
            }

            $lines[] = sprintf(
                '- [id=%s, importance=%d, categories=%s] %s',
                $memory->id,
                $memory->importance,
                implode(',', $memory->categories ?? []),
                $memory->content,
            );
        }

        return sprintf(
            "Review these semantically similar memories (average similarity %.2f) and decide whether to merge or keep separate:\n\n%s",
            $cluster->averageSimilarity,
            implode("\n", $lines),
        );
    }
}
