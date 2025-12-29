<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetUserDiabetesLogsAction
{
    /**
     * Execute the action.
     *
     * @return LengthAwarePaginator<int, DiabetesLog>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->diabetesLogs()->paginate($perPage);
    }
}
