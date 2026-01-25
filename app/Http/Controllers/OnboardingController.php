<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Sex;
use App\Http\Requests\StoreBiometricsRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OnboardingController
{
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

        return to_route('onboarding.identity.show');
    }

    public function showIdentity(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('onboarding/identity', [
            'profile' => $profile,
        ]);
    }

    public function storeIdentity(\App\Http\Requests\StoreIdentityRequest $request): RedirectResponse
    {
        $user = $this->user;
        /** @var string $goalChoiceValue */
        $goalChoiceValue = $request->validated('goal_choice');
        /** @var string $animalProductChoiceValue */
        $animalProductChoiceValue = $request->validated('animal_product_choice');
        /** @var string $intensityChoiceValue */
        $intensityChoiceValue = $request->validated('intensity_choice');

        $goalChoice = \App\Enums\GoalChoice::from($goalChoiceValue);
        $animalProductChoice = \App\Enums\AnimalProductChoice::from($animalProductChoiceValue);
        $intensityChoice = \App\Enums\IntensityChoice::from($intensityChoiceValue);

        $dietType = \App\Services\DietMapper::map($goalChoice, $animalProductChoice, $intensityChoice);
        $activityMultiplier = \App\Services\DietMapper::getActivityMultiplier($goalChoice, $intensityChoice);

        $profileData = [
            'goal_choice' => $goalChoice->value,
            'animal_product_choice' => $animalProductChoice->value,
            'intensity_choice' => $intensityChoice->value,
            'calculated_diet_type' => $dietType->value,
            'derived_activity_multiplier' => $activityMultiplier,
        ];

        $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

        $profile = $user->profile()->first();
        if ($profile) {
            $profile->update([
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);
        }

        return to_route('meal-plans.create');
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
