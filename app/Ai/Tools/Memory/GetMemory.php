<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Contracts\Ai\Memory\GetMemoryTool;
use App\Data\Memory\MemoryData;
use App\Models\Memory;
use Illuminate\Support\Facades\Auth;

final readonly class GetMemory implements GetMemoryTool
{
    public function execute(string $memoryId, bool $includeArchived = false): MemoryData
    {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->where('id', $memoryId);

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        $memory = $query->first();

        throw_unless($memory instanceof Memory, MemoryNotFoundException::class, $memoryId);

        $memory->recordAccess();

        return $memory->toMemoryData();
    }
}
