<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Enums\HealthSyncType;
use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowHealthEntryController
{
    public function __construct(
        private HealthEntryAssembler $assembler,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthSyncSample $healthSyncSample): Response
    {
        Gate::authorize('view', $healthSyncSample);

        $entryTypes = HealthSyncType::entryTypeValues();
        abort_unless(in_array($healthSyncSample->type_identifier, $entryTypes, true), 404);

        $timezone = $this->currentUser->resolveTimezone();
        $memberTypes = [...$entryTypes, HealthSyncType::Fiber->value];
        $members = $healthSyncSample->groupMembers()
            ->filter(fn (HealthSyncSample $member): bool => in_array($member->type_identifier, $memberTypes, true));

        return Inertia::render('health-entries/show', [
            'entry' => $this->assembler->assemble($members, $timezone)->sole(),
            'date' => $healthSyncSample->measured_at->toImmutable()->setTimezone($timezone)->toDateString(),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
