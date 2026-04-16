<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Agents\Memory\MemoryCategorizerAgent;
use App\Contracts\Ai\Memory\CategorizeMemoriesTool;
use App\Models\Memory;
use Illuminate\Support\Facades\Auth;

final readonly class CategorizeMemories implements CategorizeMemoriesTool
{
    public function __construct(private MemoryCategorizerAgent $agent) {}

    /**
     * @param  array<string>  $memoryIds
     * @return array<string, array<string>|null>
     */
    public function execute(array $memoryIds, bool $persistCategories = true): array
    {
        if ($memoryIds === []) {
            return [];
        }

        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->whereIn('id', $memoryIds);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $memories = $query->get()->keyBy('id');

        $result = [];

        foreach ($memoryIds as $memoryId) {
            /** @var Memory|null $memory */
            $memory = $memories->get($memoryId);

            if (! $memory instanceof Memory) {
                $result[$memoryId] = null;

                continue;
            }

            $categories = $this->agent->categorize($memory->content);
            $result[$memoryId] = $categories;

            if ($persistCategories && $categories !== []) {
                $existing = $memory->categories ?? [];
                $merged = array_values(array_unique([...$existing, ...$categories]));
                $memory->categories = $merged;
                $memory->save();
            }
        }

        return $result;
    }
}
