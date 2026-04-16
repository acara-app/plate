<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Contracts\Ai\Memory\SearchMemoryTool;
use App\Data\Memory\MemorySearchResultData;
use App\Models\Memory;
use App\Services\Memory\EmbeddingService;
use App\Services\Memory\VectorStoreService;
use Illuminate\Support\Facades\Auth;

final readonly class SearchMemory implements SearchMemoryTool
{
    public function __construct(
        private EmbeddingService $embedder,
        private VectorStoreService $vectorStore,
    ) {}

    /**
     * @param  array<string, mixed>  $filter
     * @return array<int, MemorySearchResultData>
     */
    public function execute(
        string $query,
        int $limit = 5,
        float $minRelevance = 0.7,
        array $filter = [],
        bool $includeArchived = false,
    ): array {
        $userId = isset($filter['user_id']) && is_numeric($filter['user_id'])
            ? (int) $filter['user_id']
            : (int) (Auth::id() ?? 0);

        if ($userId <= 0) {
            return [];
        }

        unset($filter['user_id']);

        $queryVector = $this->embedder->generate($query);

        $results = $this->vectorStore->search(
            queryVector: $queryVector,
            userId: $userId,
            limit: $limit,
            minRelevance: $minRelevance,
            filter: $filter,
            includeArchived: $includeArchived,
        );

        return array_map(
            static function (array $row): MemorySearchResultData {
                /** @var Memory $memory */
                $memory = $row['memory'];
                $memory->recordAccess();

                return $memory->toSearchResultData($row['score']);
            },
            $results,
        );
    }
}
