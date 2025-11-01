<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GlucoseReading;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetUserGlucoseReadingsAction
{
    /**
     * Execute the action.
     *
     * @return LengthAwarePaginator<int, GlucoseReading>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->glucoseReadings()->paginate($perPage);
    }
}
