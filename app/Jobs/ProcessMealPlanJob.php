<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateMealPlan;
use App\Actions\StoreMealPlan;
use App\Enums\AiModel;
use App\Models\User;
use App\Traits\Trackable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ProcessMealPlanJob implements ShouldQueue
{
    use Queueable;
    use Trackable;

    public const string JOB_TYPE = 'meal_plan_generation';

    public int $timeout = 300;

    public function __construct(
        public readonly int $userId,
        public readonly AiModel $model = AiModel::Gemini25Flash,
    ) {
        //
    }

    public function handle(GenerateMealPlan $generateMealPlan, StoreMealPlan $storeMealPlan): void
    {
        try {
            /** @var User|null $user */
            $user = User::query()->find($this->userId);

            if (! $user) {
                return;
            }

            // Initialize tracking only when the job actually runs
            $this->initializeTracking($this->userId, self::JOB_TYPE);

            $this->startTracking('Starting meal plan generation...');

            $this->updateTrackingProgress(25, 'Creating personalized prompt...');

            $mealPlanData = $generateMealPlan->handle($user, $this->model);

            $this->updateTrackingProgress(75, 'Saving meal plan...');

            $storeMealPlan->handle($user, $mealPlanData);

            $this->completeTracking('Meal plan generated successfully!');
        } catch (Throwable $e) {
            $this->failTracking('Failed to generate meal plan: '.$e->getMessage());

            throw $e;
        }
    }
}
