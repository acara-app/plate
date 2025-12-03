<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AiModel;
use App\Models\User;
use App\Workflows\GenerateMealPlanWorkflow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Workflow\WorkflowStub;

final class ProcessMealPlanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public readonly User $user,
        public readonly AiModel $aiModel = AiModel::Gemini25Flash,
        public readonly int $totalDays = 7,
    ) {
        //
    }

    public function handle(): void
    {

        $workflow = WorkflowStub::make(GenerateMealPlanWorkflow::class);

        $workflow->start($this->user, $this->totalDays, $this->aiModel);
    }
}
