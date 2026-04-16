<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Contracts\Ai\Memory\GetRelatedMemoriesTool;
use App\Data\Memory\RelatedMemoryData;
use App\Models\Memory;
use App\Models\MemoryLink;
use Illuminate\Support\Facades\Auth;

final readonly class GetRelatedMemories implements GetRelatedMemoriesTool
{
    /**
     * @param  array<string>  $relationships
     * @return array<int, RelatedMemoryData>
     */
    public function execute(
        string $memoryId,
        int $depth = 1,
        array $relationships = [],
        bool $includeArchived = false,
    ): array {
        $userId = (int) (Auth::id() ?? 0);

        $rootQuery = Memory::query()->where('id', $memoryId);
        if ($userId > 0) {
            $rootQuery->where('user_id', $userId);
        }

        throw_unless($rootQuery->exists(), MemoryNotFoundException::class, $memoryId);

        $visited = [$memoryId => 0];
        $results = [];
        $frontier = [$memoryId];

        for ($level = 1; $level <= $depth; $level++) {
            if ($frontier === []) {
                break;
            }

            $linkQuery = MemoryLink::query()->whereIn('source_memory_id', $frontier);

            if ($relationships !== []) {
                $linkQuery->whereIn('relationship', $relationships);
            }

            $links = $linkQuery->get();

            $nextFrontier = [];

            foreach ($links as $link) {
                if (isset($visited[$link->target_memory_id])) {
                    continue;
                }

                $visited[$link->target_memory_id] = $level;

                $targetQuery = Memory::query()->where('id', $link->target_memory_id);
                if ($userId > 0) {
                    $targetQuery->where('user_id', $userId);
                }

                if (! $includeArchived) {
                    $targetQuery->where('is_archived', false);
                }

                $target = $targetQuery->first();
                if (! $target instanceof Memory) {
                    continue;
                }

                $results[] = $target->toRelatedData($link->relationship, $level);
                $nextFrontier[] = $link->target_memory_id;
            }

            $frontier = $nextFrontier;
        }

        return $results;
    }
}
