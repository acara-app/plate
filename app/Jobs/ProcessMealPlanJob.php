<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateMealPlan;
use App\Actions\StoreMealPlan;
use App\Enums\AiModel;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessMealPlanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public readonly int $userId,
        public readonly AiModel $model = AiModel::Gemini25Flash,
    ) {
        //
    }

    public function handle(GenerateMealPlan $generateMealPlan, StoreMealPlan $storeMealPlan): void
    {
        /** @var User|null $user */
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        $mealPlanData = $generateMealPlan->handle($user, $this->model);
        $storeMealPlan->handle($user, $mealPlanData);
    }
}
