<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Contracts\Ai\Memory\GetMemoryStatTool;
use App\Data\Memory\MemoryStatsData;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final readonly class GetMemoryStat implements GetMemoryStatTool
{
    public function execute(): MemoryStatsData
    {
        $userId = (int) (Auth::id() ?? 0);

        $baseQuery = fn (): Builder => $userId > 0
            ? Memory::query()->where('user_id', $userId)
            : Memory::query();

        $total = $baseQuery()->count();
        $active = $baseQuery()->where('is_archived', false)->count();
        $archived = $baseQuery()->where('is_archived', true)->count();

        $categoriesCount = $this->buildCategoriesCount($baseQuery());
        $importanceDistribution = $this->buildImportanceDistribution($baseQuery());

        $storageBytes = 0;
        $baseQuery()->select(['content', 'embedding'])->chunk(500, function ($chunk) use (&$storageBytes): void {
            foreach ($chunk as $memory) {
                $storageBytes += mb_strlen((string) $memory->content);
                $storageBytes += mb_strlen((string) $memory->embedding);
            }
        });

        $lastUpdate = $baseQuery()->max('updated_at');
        $expiringCount = $baseQuery()
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDay())
            ->count();

        return new MemoryStatsData(
            totalMemories: $total,
            activeMemories: $active,
            archivedMemories: $archived,
            lastUpdate: is_string($lastUpdate) ? $lastUpdate : null,
            categoriesCount: $categoriesCount,
            importanceDistribution: $importanceDistribution,
            storageSizeMb: round($storageBytes / 1024 / 1024, 4),
            expiringCount: $expiringCount,
        );
    }

    /**
     * @param  Builder<Memory>  $query
     * @return array<string, int>
     */
    private function buildCategoriesCount(Builder $query): array
    {
        $counts = [];

        $query->select('categories')->chunk(500, function ($chunk) use (&$counts): void {
            foreach ($chunk as $memory) {
                foreach ($memory->categories ?? [] as $category) {
                    $counts[$category] = ($counts[$category] ?? 0) + 1;
                }
            }
        });

        return $counts;
    }

    /**
     * @param  Builder<Memory>  $query
     * @return array<int, int>
     */
    private function buildImportanceDistribution(Builder $query): array
    {
        $distribution = array_fill(1, 10, 0);

        $query->selectRaw('importance, COUNT(*) as total')
            ->groupBy('importance')
            ->get()
            ->each(function ($row) use (&$distribution): void {
                $level = (int) $row->importance;
                if ($level >= 1 && $level <= 10) {
                    $distribution[$level] = (int) $row->total;
                }
            });

        return $distribution;
    }
}
