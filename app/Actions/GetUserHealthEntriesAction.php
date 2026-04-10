<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @codeCoverageIgnore */
final readonly class GetUserHealthEntriesAction
{
    /**
     * @return LengthAwarePaginator<int, HealthSyncSample>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->healthSyncSamples()
            ->whereIn('type_identifier', HealthSyncType::entryTypeValues())
            ->latest('measured_at')
            ->paginate($perPage);
    }
}
