<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetUserHealthEntriesAction
{
    /**
     * Execute the action.
     *
     * @return LengthAwarePaginator<int, HealthEntry>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->healthEntries()->paginate($perPage);
    }
}
