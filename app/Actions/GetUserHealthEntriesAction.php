<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class GetUserHealthEntriesAction
{
    private const string STANDALONE_PREFIX = 'standalone_';

    public function __construct(private HealthEntryAssembler $assembler) {}

    /**
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginatorContract
    {
        $entryTypes = HealthSyncType::entryTypeValues();
        $entryKey = "COALESCE(CAST(group_id AS TEXT), '".self::STANDALONE_PREFIX."' || id)";

        $entries = $user->healthSyncSamples()
            ->whereIn('type_identifier', $entryTypes)
            ->selectRaw($entryKey . ' as entry_key, MAX(measured_at) as sort_at')
            ->groupByRaw($entryKey)
            ->latest('sort_at')
            ->orderByDesc('entry_key')
            ->paginate($perPage);

        return new LengthAwarePaginator(
            $this->assemblePage($user, $entryTypes, $entries->getCollection()),
            $entries->total(),
            $entries->perPage(),
            $entries->currentPage(),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @param  array<int, string>  $entryTypes
     * @param  Collection<int, HealthSyncSample>  $entryRows
     * @return array<int, array<string, mixed>>
     */
    private function assemblePage(User $user, array $entryTypes, Collection $entryRows): array
    {
        $keys = $entryRows
            ->pluck('entry_key')
            ->map(fn (mixed $key): string => is_scalar($key) ? (string) $key : '');

        if ($keys->isEmpty()) {
            return [];
        }

        $groupIds = $keys
            ->reject(fn (string $key): bool => str_starts_with($key, self::STANDALONE_PREFIX))
            ->values()
            ->all();

        $standaloneIds = $keys
            ->filter(fn (string $key): bool => str_starts_with($key, self::STANDALONE_PREFIX))
            ->map(fn (string $key): int => (int) Str::after($key, self::STANDALONE_PREFIX))
            ->values()
            ->all();

        $samples = $user->healthSyncSamples()
            ->whereIn('type_identifier', $entryTypes)
            ->where(function (Builder $query) use ($groupIds, $standaloneIds): void {
                $query
                    ->when($groupIds !== [], fn (Builder $inner): Builder => $inner->whereIn('group_id', $groupIds))
                    ->when($standaloneIds !== [], fn (Builder $inner): Builder => $inner->orWhereIn('id', $standaloneIds));
            })
            ->get();

        return $this->assembler->assemble($samples)->values()->all();
    }
}
