<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MealPlanGenerationStatus;
use App\Enums\Sex;
use App\Http\Requests\StoreBiometricsRequest;
use App\Http\Requests\StoreDietaryPreferencesRequest;
use App\Http\Requests\StoreGoalsRequest;
use App\Http\Requests\StoreHealthConditionsRequest;
use App\Http\Requests\StoreLifestyleRequest;
use App\Models\DietaryPreference;
use App\Models\Goal;
use App\Models\HealthCondition;
use App\Models\Lifestyle;
use App\Models\MealPlan;
use App\Models\User;
use App\Models\UserProfile;
use App\Workflows\MealPlanInitializeWorkflow;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Workflow\WorkflowStub;

final readonly class OnboardingController
{
    private const int DEFAULT_DURATION_DAYS = 7;

    public function __construct(
        #[CurrentUser] private User $user,
    ) {
        //
    }

    public function showQuestionnaire(): Response
    {
        return Inertia::render('onboarding/questionnaire');
    }

    public function showBiometrics(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/biometrics', [
            'profile' => $profile,
            'sexOptions' => collect(Sex::cases())->map(fn (Sex $sex): array => [
                'value' => $sex->value,
                'label' => ucfirst($sex->value),
            ]),
        ]);
    }

    public function storeBiometrics(StoreBiometricsRequest $request): RedirectResponse
    {
        $user = $this->user;

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return to_route('onboarding.goals.show');
    }

    public function showGoals(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/goals', [
            'profile' => $profile,
            'goals' => Goal::all(),
        ]);
    }

    public function storeGoals(StoreGoalsRequest $request): RedirectResponse
    {
        $user = $this->user;

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return to_route('onboarding.lifestyle.show');
    }

    public function showLifestyle(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/life-style-page', [
            'profile' => $profile,
            'lifestyles' => Lifestyle::all(),
        ]);
    }

    public function storeLifestyle(StoreLifestyleRequest $request): RedirectResponse
    {
        $user = $this->user;

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return to_route('onboarding.dietary-preferences.show');
    }

    public function showDietaryPreferences(): Response
    {
        $profile = $this->user->profile;

        $preferences = DietaryPreference::all()->groupBy('type');

        return Inertia::render('onboarding/dietary-preferences', [
            'profile' => $profile,
            'selectedPreferences' => $profile?->dietaryPreferences->pluck('id')->toArray() ?? [],
            'preferences' => $preferences,
        ]);
    }

    public function storeDietaryPreferences(StoreDietaryPreferencesRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<int, int> $preferenceIds */
        $preferenceIds = $request->validated('dietary_preference_ids') ?? [];
        $profile->dietaryPreferences()->sync($preferenceIds);

        return to_route('onboarding.health-conditions.show');
    }

    public function showHealthConditions(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/health-conditions', [
            'profile' => $profile,
            'selectedConditions' => $profile?->healthConditions->pluck('id')->toArray() ?? [],
            'healthConditions' => HealthCondition::all(),
        ]);
    }

    public function storeHealthConditions(StoreHealthConditionsRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<int, int> $conditionIds */
        $conditionIds = $request->validated('health_condition_ids') ?? [];
        /** @var array<int, string|null> $notes */
        $notes = $request->validated('notes') ?? [];

        $syncData = [];
        foreach ($conditionIds as $index => $conditionId) {
            $syncData[$conditionId] = [
                'notes' => $notes[$index] ?? null,
            ];
        }

        $profile->healthConditions()->sync($syncData);

        // Mark onboarding as completed
        $profile->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        // Create meal plan synchronously with Generating status so users
        $mealPlan = $this->createMealPlanWithGeneratingStatus($user);

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, self::DEFAULT_DURATION_DAYS, null, $mealPlan);

        return to_route('onboarding.completion.show');
    }

    public function showCompletion(): Response|RedirectResponse
    {
        $user = $this->user;

        if (! $user->profile?->onboarding_completed) {
            return to_route('onboarding.questionnaire.show');
        }

        return Inertia::render('onboarding/completion');
    }

    private function createMealPlanWithGeneratingStatus(User $user): MealPlan
    {
        $mealPlanType = MealPlanInitializeWorkflow::getMealPlanType(self::DEFAULT_DURATION_DAYS);

        /** @var MealPlan $mealPlan */
        $mealPlan = $user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => self::DEFAULT_DURATION_DAYS.'-Day Personalized Meal Plan',
            'description' => 'AI-generated meal plan tailored to your nutritional needs and preferences.',
            'duration_days' => self::DEFAULT_DURATION_DAYS,
            'target_daily_calories' => null,
            'macronutrient_ratios' => null,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generation_method' => 'workflow',
                'status' => MealPlanGenerationStatus::Generating->value,
                'days_completed' => 0,
            ],
        ]);

        return $mealPlan;
    }
}
