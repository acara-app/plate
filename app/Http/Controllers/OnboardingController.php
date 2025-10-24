<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OnboardingController
{
    public function __construct(#[CurrentUser] private readonly \App\Models\User $user) {}

    public function showQuestionnaire(): Response|RedirectResponse
    {
        $user = $this->user;

        if ($user->profile?->onboarding_completed) {
            return to_route('dashboard');
        }

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

        return Inertia::render('onboarding/lifestyle', [
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

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $profile->dietaryPreferences()->sync($request->validated('dietary_preference_ids') ?? []);

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

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $conditionIds = $request->validated('health_condition_ids') ?? [];
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

        return to_route('dashboard')->with('success', 'Onboarding completed successfully!');
    }
}
