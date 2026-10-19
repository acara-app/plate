<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Enums\MealPhotoRoute;
use App\Models\HealthSyncSample;
use App\Services\HealthEntryAssembler;

final readonly class GetHealthEntryAction
{
    public function __construct(private HealthEntryAssembler $assembler) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(HealthSyncSample $sample, string $timezone, MealPhotoRoute $photoRoute = MealPhotoRoute::Web): array
    {
        $memberTypes = [...HealthSyncType::entryTypeValues(), ...HealthSyncType::groupOnlyValues()];
        $members = $sample->groupMembers()
            ->filter(fn (HealthSyncSample $member): bool => in_array($member->type_identifier, $memberTypes, true));

        return $this->assembler->assemble($members, $timezone, $photoRoute)->sole();
    }
}
