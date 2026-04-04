<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataObjects\DietIdentityData;
use App\Enums\AllergySeverity;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Enums\UserProfileAttributeCategory;
use App\Http\Requests\StoreBiometricsRequest;
use App\Http\Requests\StoreDietaryPreferencesRequest;
use App\Http\Requests\StoreIdentityRequest;
use App\Models\User;
use App\Services\DietMapper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
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
            'bloodTypeOptions' => collect(BloodType::cases())->map(fn (BloodType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
        ]);
    }

    public function storeBiometrics(StoreBiometricsRequest $request): RedirectResponse
    {
        $user = $this->user;

        $data = $request->validated();

        if (isset($data['date_of_birth']) && is_string($data['date_of_birth'])) {
            $data['age'] = Date::parse($data['date_of_birth'])->age;
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
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

        return to_route('onboarding.dietary.show');
    }

    public function showDietaryPreferences(): Response
    {
        $profile = $this->user->profile;
        $existingAttributes = $profile
            ? $profile->dietaryAttributes
            : collect();

        return Inertia::render('onboarding/dietary-preferences', [
            'existingAttributes' => $existingAttributes,
            'categories' => collect([
                UserProfileAttributeCategory::Allergy,
                UserProfileAttributeCategory::Intolerance,
                UserProfileAttributeCategory::Dislike,
                UserProfileAttributeCategory::Restriction,
            ])->map(fn (UserProfileAttributeCategory $cat): array => [
                'value' => $cat->value,
                'label' => $cat->label(),
            ]),
            'severityOptions' => collect(AllergySeverity::cases())->map(fn (AllergySeverity $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function storeDietaryPreferences(StoreDietaryPreferencesRequest $request): RedirectResponse
    {
        $user = $this->user;
        $profile = $user->profile()->firstOrFail();

        $profile->attributes()
            ->whereIn('category', [
                UserProfileAttributeCategory::Allergy,
                UserProfileAttributeCategory::Intolerance,
                UserProfileAttributeCategory::Dislike,
                UserProfileAttributeCategory::Restriction,
            ])
            ->delete();

        /** @var array<int, array{category: string, value: string, severity?: string|null, notes?: string|null}> $attributes */
        $attributes = $request->validated('attributes', []);

        foreach ($attributes as $attr) {
            $profile->attributes()->create([
                'category' => $attr['category'],
                'value' => $attr['value'],
                'severity' => $attr['severity'] ?? null,
                'notes' => $attr['notes'] ?? null,
            ]);
        }

        $profile->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        return to_route('dashboard');
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
