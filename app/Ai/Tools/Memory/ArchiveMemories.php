<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Contracts\Ai\Memory\ArchiveMemoriesTool;
use App\Models\Memory;
use Illuminate\Support\Facades\Auth;

final readonly class ArchiveMemories implements ArchiveMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds
     */
    public function execute(array $memoryIds): int
    {
        if ($memoryIds === []) {
            return 0;
        }

        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->whereIn('id', $memoryIds);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        return $query->update(['is_archived' => true]);
    }
}
