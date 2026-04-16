<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Contracts\Ai\Memory\LinkMemoriesTool;
use App\Models\Memory;
use App\Models\MemoryLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class LinkMemories implements LinkMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds
     */
    public function execute(
        array $memoryIds,
        string $relationship = 'related',
        bool $bidirectional = true,
    ): bool {
        if (count($memoryIds) < 2) {
            return false;
        }

        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->whereIn('id', $memoryIds);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $found = $query->pluck('id')->all();

        foreach ($memoryIds as $memoryId) {
            throw_unless(in_array($memoryId, $found, true), MemoryNotFoundException::class, $memoryId);
        }

        try {
            DB::transaction(function () use ($memoryIds, $relationship, $bidirectional): void {
                $ids = array_values($memoryIds);
                $n = count($ids);

                for ($i = 0; $i < $n; $i++) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        MemoryLink::query()->firstOrCreate([
                            'source_memory_id' => $ids[$i],
                            'target_memory_id' => $ids[$j],
                            'relationship' => $relationship,
                        ]);

                        if ($bidirectional) {
                            MemoryLink::query()->firstOrCreate([
                                'source_memory_id' => $ids[$j],
                                'target_memory_id' => $ids[$i],
                                'relationship' => $relationship,
                            ]);
                        }
                    }
                }
            });

            return true;
        } catch (Throwable $throwable) {
            throw new MemoryStorageException(
                message: 'Failed to link memories: '.$throwable->getMessage(),
                operation: 'link',
                context: ['memory_ids' => $memoryIds],
            );
        }
    }
}
