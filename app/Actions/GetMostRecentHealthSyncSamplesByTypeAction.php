<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

final readonly class GetMostRecentHealthSyncSamplesByTypeAction
{
    /**
     * @param  array<int, string>|null  $typeFilter
     * @return Collection<string, HealthSyncSample>
     */
    public function handle(User $user, ?array $typeFilter = null): Collection
    {
        $latestPerType = $user->healthSyncSamples()
            ->whereNotIn('type_identifier', HealthSyncType::userCharacteristicValues())
            ->selectRaw('type_identifier, MAX(measured_at) as latest_measured_at')
            ->groupBy('type_identifier');

        if ($typeFilter !== null) {
            $latestPerType->whereIn('type_identifier', $typeFilter);
        }

        return $user->healthSyncSamples()
            ->joinSub($latestPerType->toBase(), 'latest', function (JoinClause $join): void {
                $join->on('health_sync_samples.type_identifier', '=', 'latest.type_identifier')
                    ->on('health_sync_samples.measured_at', '=', 'latest.latest_measured_at');
            })
            ->get()
            ->keyBy('type_identifier');
    }
}
