<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataObjects\DietIdentityData;
use App\Enums\AnimalProductChoice;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Http\Requests\StoreBiometricsRequest;
use App\Http\Requests\StoreIdentityRequest;
use App\Models\User;
use App\Services\DietMapper;
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

    public function storeIdentity(StoreIdentityRequest $request): RedirectResponse
    {
        $user = $this->user;
        $dietIdentityData = DietIdentityData::from($request->validated());

        $dietType = DietMapper::map(
            GoalChoice::from($dietIdentityData->goal_choice),
            AnimalProductChoice::from($dietIdentityData->animal_product_choice),
            IntensityChoice::from($dietIdentityData->intensity_choice)
        );

        $activityMultiplier = DietMapper::getActivityMultiplier(
            GoalChoice::from($dietIdentityData->goal_choice),
            IntensityChoice::from($dietIdentityData->intensity_choice)
        );

        $profileData = [
            'goal_choice' => GoalChoice::from($dietIdentityData->goal_choice),
            'animal_product_choice' => AnimalProductChoice::from($dietIdentityData->animal_product_choice),
            'intensity_choice' => IntensityChoice::from($dietIdentityData->intensity_choice),
            'calculated_diet_type' => $dietType,
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

        return to_route('chat.create');
    }

    public function showCompletion(): Response|RedirectResponse
    {
        $user = $this->user;

        if (! $user->profile?->onboarding_completed) {
            return to_route('onboarding.biometrics.show');
        }

        return Inertia::render('onboarding/completion');
    }
}
