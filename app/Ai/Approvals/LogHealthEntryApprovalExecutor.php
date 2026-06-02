<?php

declare(strict_types=1);

namespace App\Ai\Approvals;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\RecordHealthSampleAction;
use App\Contracts\ExecutesApprovedTool;
use App\Data\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Models\AgentApproval;
use Carbon\CarbonImmutable;

final readonly class LogHealthEntryApprovalExecutor implements ExecutesApprovedTool
{
    public function __construct(
        private RecordHealthSampleAction $recordHealthSample,
        private AggregateHealthDailySamplesAction $aggregateHealthDailySamples,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(AgentApproval $approval): array
    {
        $data = HealthLogData::fromParsedArray($approval->payload);
        $user = $approval->user;

        $sample = $this->recordHealthSample->handle($data, $user, HealthEntrySource::Chat);

        $measuredAt = $data->measuredAt ?? CarbonImmutable::now('UTC');
        $this->aggregateHealthDailySamples->handle($user, $measuredAt->setTimezone('UTC')->startOfDay());

        return [
            'entry_id' => $sample->id,
            'group_id' => $sample->group_id,
        ];
    }
}
