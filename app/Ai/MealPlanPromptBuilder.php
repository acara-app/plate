<?php

declare(strict_types=1);

namespace App\Ai;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanContext\MacronutrientRatiosData;
use App\DataObjects\MealPlanContext\MealPlanContextData;
use App\DataObjects\PreviousDayContext;
use App\Enums\AllergySeverity;
use App\Enums\AnimalProductChoice;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\UserProfileAttributeCategory;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use App\Services\DietMapper;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

final readonly class MealPlanPromptBuilder
{
    public function __construct(
        private GlucoseDataAnalyzer $glucoseDataAnalyzer,
    ) {}

    public function handle(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): string
    {
        $context = $this->buildContext($user, $glucoseAnalysis);

        return view('ai.agents.create-meal-plan', [
            'context' => $context,
        ])->render();
    }

    public function handleForDay(
        User $user,
        int $dayNumber,
        int $totalDays = 7,
        ?PreviousDayContext $previousDaysContext = null,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?string $customPrompt = null,
    ): string {
        $context = $this->buildContext($user, $glucoseAnalysis);

        return view('ai.agents.create-day-meal-plan', [
            'context' => $context,
            'dayNumber' => $dayNumber,
            'totalDays' => $totalDays,
            'previousDaysContext' => $previousDaysContext?->toPromptText(),
            'prompt' => $customPrompt,
        ])->render();
    }

    private function buildContext(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): MealPlanContextData
    {
        $user->loadMissing([
            'profile.attributes',
        ]);

        throw_unless($user->profile instanceof UserProfile, RuntimeException::class, 'User profile is required to create a meal plan.');
        /**
         * @var UserProfile $profile
         */
        $profile = $user->profile;

        $dietType = $this->calculateDietType($profile);
        $macroTargets = $dietType->macroTargets();

        return MealPlanContextData::from([
            ...$profile->toArray(),
            'goal' => $profile->goal_choice?->label(),
            'dietary_preferences' => $this->enrichAttributes($profile->dietaryAttributes()->get()),
            'health_conditions' => $this->enrichAttributes($profile->healthConditionAttributes()->get()),
            'medications' => $this->enrichAttributes($profile->medicationAttributes()->get()),
            'daily_calorie_target' => $this->calculateDailyCalorieTarget($profile),
            'macronutrient_ratios' => new MacronutrientRatiosData(
                protein: $macroTargets['protein'],
                carbs: $macroTargets['carbs'],
                fat: $macroTargets['fat'],
            ),
            'diet_type' => $dietType,
            'diet_type_label' => $dietType->label(),
            'diet_type_focus' => $dietType->focus(),
            'glucose_analysis' => $glucoseAnalysis ?? $this->glucoseDataAnalyzer->handle($user, 30),
        ]);
    }

    /**
     * @param  Collection<int, UserProfileAttribute>  $attributes
     * @return array<int, array{category: UserProfileAttributeCategory, value: string, severity: AllergySeverity|null, notes: string|null, metadata: array<string, mixed>|null}>
     */
    private function enrichAttributes(Collection $attributes): array
    {
        return $attributes->map(fn (UserProfileAttribute $attr): array => [
            'category' => $attr->category,
            'value' => $attr->value,
            'severity' => $attr->severity,
            'notes' => $attr->notes,
            'metadata' => $attr->metadata,
        ])->values()->all();
    }

    private function calculateDietType(UserProfile $profile): DietType
    {
        return DietMapper::map(
            $profile->goal_choice ?? GoalChoice::HealthyEating,
            $profile->animal_product_choice ?? AnimalProductChoice::Omnivore,
            $profile->intensity_choice ?? IntensityChoice::Balanced,
        );
    }

    private function calculateDailyCalorieTarget(UserProfile $profile): ?float
    {
        $tdee = $profile->tdee;

        if (! $tdee || ! $profile->goal_choice) {
            return null;
        }

        return match ($profile->goal_choice) {
            GoalChoice::WeightLoss => round($tdee - 500, 2),
            GoalChoice::BuildMuscle => round($tdee + 300, 2),
            GoalChoice::Spikes, GoalChoice::HeartHealth, GoalChoice::HealthyEating => round($tdee - 300, 2),
        };
    }
}
