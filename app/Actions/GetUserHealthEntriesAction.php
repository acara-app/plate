<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class GetUserHealthEntriesAction
{
    public function __construct(private HealthEntryAssembler $assembler) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(User $user, CarbonImmutable $day): Collection
    {
        $timezone = $day->getTimezone()->getName();
        $start = $day->startOfDay();

        $samples = $user->healthSyncSamples()
            ->whereIn('type_identifier', HealthSyncType::entryTypeValues())
            ->where('measured_at', '>=', $start->utc())
            ->where('measured_at', '<', $start->addDay()->utc())
            ->get();

        return $this->assembler->assemble($samples, $timezone);
    }
}
