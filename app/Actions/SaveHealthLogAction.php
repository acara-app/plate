<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Models\User;
use Carbon\CarbonInterface;

final readonly class SaveHealthLogAction
{
    public function __construct(
        private RecordHealthEntryAction $recordHealthEntry,
    ) {}

    /**
     * Save health log data for a user.
     *
     * @param  User  $user  The user to save the log for
     * @param  HealthLogData  $data  The health log data
     * @param  ?CarbonInterface  $measuredAt  Optional custom measurement time
     */
    public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
    {
        $recordData = $this->buildRecordData($user, $data, $measuredAt);
        $this->recordHealthEntry->handle($recordData, HealthEntrySource::Telegram);
    }

    /**
     * Build the record data array for database storage.
     *
     * @return array<string, mixed>
     */
    private function buildRecordData(User $user, HealthLogData $data, ?CarbonInterface $measuredAt): array
    {
        $recordData = [
            'user_id' => $user->id,
            'measured_at' => $measuredAt ?? now(),
            'notes' => null,
        ];

        $typeSpecificData = $data->toRecordArray();

        return array_merge($recordData, $typeSpecificData);
    }
}
