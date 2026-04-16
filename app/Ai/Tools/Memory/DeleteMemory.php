<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\UnscopedMemoryOperationException;
use App\Contracts\Ai\Memory\DeleteMemoryTool;
use App\Models\Memory;
use App\Services\Memory\MemoryFilterValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final readonly class DeleteMemory implements DeleteMemoryTool
{
    public function __construct(private MemoryFilterValidator $filterValidator) {}

    /**
     * @param  array<string, mixed>  $filter
     */
    public function execute(?string $memoryId = null, array $filter = []): int
    {
        $scopedUserId = $this->resolveScopedUserId($filter);

        if ($memoryId !== null) {
            $memory = Memory::query()
                ->where('id', $memoryId)
                ->where('user_id', $scopedUserId)
                ->first();

            throw_unless($memory instanceof Memory, MemoryNotFoundException::class, $memoryId);

            return $memory->delete() ? 1 : 0;
        }

        $this->filterValidator->requireNonEmpty($filter);

        $query = Memory::query()->where('user_id', $scopedUserId);

        $this->applyFilter($query, $filter);

        return $query->delete();
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function resolveScopedUserId(array $filter): int
    {
        if (isset($filter['user_id'])) {
            $fromFilter = (int) $filter['user_id'];
            if ($fromFilter > 0) {
                return $fromFilter;
            }
        }

        $fromAuth = (int) (Auth::id() ?? 0);
        if ($fromAuth > 0) {
            return $fromAuth;
        }

        throw UnscopedMemoryOperationException::forDelete();
    }

    /**
     * @param  Builder<Memory>  $query
     * @param  array<string, mixed>  $filter
     */
    private function applyFilter(Builder $query, array $filter): void
    {
        if (isset($filter['category']) && is_string($filter['category'])) {
            $query->whereJsonContains('categories', $filter['category']);
        }

        if (isset($filter['categories']) && is_array($filter['categories'])) {
            $query->where(function (Builder $q) use ($filter): void {
                foreach ($filter['categories'] as $category) {
                    $q->orWhereJsonContains('categories', $category);
                }
            });
        }

        if (isset($filter['source']) && is_string($filter['source'])) {
            $query->where('source', $filter['source']);
        }

        if (isset($filter['importance_min'])) {
            $query->where('importance', '>=', (int) $filter['importance_min']);
        }

        if (isset($filter['importance_max'])) {
            $query->where('importance', '<=', (int) $filter['importance_max']);
        }

        if (array_key_exists('is_archived', $filter)) {
            $query->where('is_archived', (bool) $filter['is_archived']);
        }

        if (isset($filter['tags']) && is_array($filter['tags'])) {
            $query->where(function (Builder $q) use ($filter): void {
                foreach ($filter['tags'] as $tag) {
                    $q->orWhereJsonContains('metadata->tags', $tag);
                }
            });
        }
    }
}
