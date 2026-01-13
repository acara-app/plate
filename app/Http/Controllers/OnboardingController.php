<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\AllergySeverity;
use App\Enums\GlucoseUnit;
use App\Enums\Sex;
use App\Http\Requests\StoreBiometricsRequest;
use App\Http\Requests\StoreDietaryPreferencesRequest;
use App\Http\Requests\StoreGoalsRequest;
use App\Http\Requests\StoreHealthConditionsRequest;
use App\Http\Requests\StoreLifestyleRequest;
use App\Http\Requests\StoreMealPlanDurationRequest;
use App\Http\Requests\StoreMedicationsRequest;
use App\Models\DietaryPreference;
use App\Models\Goal;
use App\Models\HealthCondition;
use App\Models\Lifestyle;
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
    public function __construct(
        #[CurrentUser] private User $user,
        private AnalyzeGlucoseForNotificationAction $analyzeGlucose,
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

        $selectedPreferencesData = [];
        if ($profile) {
            foreach ($profile->dietaryPreferences as $preference) {
                /** @var object{severity: string|null, notes: string|null} $pivot */
                $pivot = $preference->pivot;
                $selectedPreferencesData[$preference->id] = [
                    'severity' => $pivot->severity,
                    'notes' => $pivot->notes,
                ];
            }
        }

        return Inertia::render('onboarding/dietary-preferences', [
            'profile' => $profile,
            'selectedPreferences' => $profile?->dietaryPreferences->pluck('id')->toArray() ?? [],
            'selectedPreferencesData' => $selectedPreferencesData,
            'preferences' => $preferences,
            'severityOptions' => collect(AllergySeverity::cases())->map(fn (AllergySeverity $severity): array => [
                'value' => $severity->value,
                'label' => $severity->label(),
                'description' => $severity->description(),
            ]),
        ]);
    }

    public function storeDietaryPreferences(StoreDietaryPreferencesRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<int, int> $preferenceIds */
        $preferenceIds = $request->validated('dietary_preference_ids') ?? [];
        /** @var array<int, string|null> $severities */
        $severities = $request->validated('severities') ?? [];
        /** @var array<int, string|null> $notes */
        $notes = $request->validated('notes') ?? [];

        $syncData = [];
        foreach ($preferenceIds as $index => $preferenceId) {
            $syncData[$preferenceId] = [
                'severity' => $severities[$index] ?? null,
                'notes' => $notes[$index] ?? null,
            ];
        }

        $profile->dietaryPreferences()->sync($syncData);

        return to_route('onboarding.health-conditions.show');
    }

    public function showHealthConditions(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/health-conditions', [
            'profile' => $profile,
            'selectedConditions' => $profile?->healthConditions->pluck('id')->toArray() ?? [],
            'healthConditions' => HealthCondition::query()->orderBy('order')->get(),
            'glucoseUnitOptions' => collect(GlucoseUnit::cases())->map(fn (GlucoseUnit $unit): array => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ]),
            'selectedGlucoseUnit' => $profile?->units_preference?->value,
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

        // Save glucose unit preference
        /** @var string|null $glucoseUnit */
        $glucoseUnit = $request->validated('units_preference');
        if ($glucoseUnit !== null) {
            $profile->update(['units_preference' => $glucoseUnit]);
        }

        return to_route('onboarding.medications.show');
    }

    public function showMedications(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/medications', [
            'profile' => $profile,
            'medications' => $profile->medications ?? [],
        ]);
    }

    public function storeMedications(StoreMedicationsRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // Clear existing medications and add new ones
        $profile->medications()->delete();

        /** @var array<int, array{name: string, dosage?: string|null, frequency?: string|null, purpose?: string|null, started_at?: string|null}> $medications */
        $medications = $request->validated('medications') ?? [];

        foreach ($medications as $medication) {
            if (! empty($medication['name'])) {
                $profile->medications()->create([
                    'name' => $medication['name'],
                    'dosage' => $medication['dosage'] ?? null,
                    'frequency' => $medication['frequency'] ?? null,
                    'purpose' => $medication['purpose'] ?? null,
                    'started_at' => $medication['started_at'] ?? null,
                ]);
            }
        }

        return to_route('onboarding.meal-plan-duration.show');
    }

    public function showMealPlanDuration(): Response
    {
        return Inertia::render('onboarding/meal-plan-duration');
    }

    public function storeMealPlanDuration(StoreMealPlanDurationRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // Mark onboarding as completed
        $profile->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        $glucoseAnalysis = $this->analyzeGlucose->handle($user);

        $durationDays = $request->integer('meal_plan_days');

        $mealPlan = MealPlanInitializeWorkflow::createMealPlan(
            $user,
            $durationDays,
        );

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, $mealPlan, $glucoseAnalysis->analysisData);

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
}
