<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Contracts\Ai\Memory\GetImportantMemoriesTool;
use App\Data\Memory\MemoryData;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final readonly class GetImportantMemories implements GetImportantMemoriesTool
{
    /**
     * @param  array<string>  $categories
     * @return array<int, MemoryData>
     */
    public function execute(
        int $threshold = 8,
        int $limit = 10,
        array $categories = [],
        bool $includeArchived = false,
    ): array {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()
            ->where('importance', '>=', $threshold)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        if ($categories !== []) {
            $query->where(function (Builder $q) use ($categories): void {
                foreach ($categories as $category) {
                    $q->orWhereJsonContains('categories', $category);
                }
            });
        }

        $memories = $query
            ->orderByDesc('importance')->latest()
            ->limit($limit)
            ->get();

        return $memories->map(static fn (Memory $m): MemoryData => $m->toMemoryData())->all();
    }
}
