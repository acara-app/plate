<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetUserHealthEntriesAction
{
    public function __construct(private HealthEntryAssembler $assembler) {}

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = $user->healthSyncSamples()
            ->whereIn('type_identifier', HealthSyncType::entryTypeValues())
            ->latest('measured_at')
            ->paginate($perPage);

        return $paginator->setCollection(
            $this->assembler->assemble($paginator->getCollection())->values()
        );
    }
}
