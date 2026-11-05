<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DataSensitivity;
use App\Enums\GoalChoice;
use App\Enums\HeightUnit;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Enums\WeightUnit;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\BodyMassIndex;
use App\Services\DietMapper;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class UpdateUserBiometrics implements Tool
{
    public function name(): string
    {
        return 'update_user_biometrics';
    }

    public function description(): string
    {
        return 'Get or update user biometric profile fields: age, date_of_birth, height, weight, sex, blood_type, goal_choice, animal_product_choice, intensity_choice, target_weight, and additional_goals. Returned height is in cm and weights are in kg. When updating, pass height and weights exactly as the user stated them together with height_unit and weight_unit; the tool converts them. If the result has requires_confirmation, ask the user to confirm before calling the tool again. Use "get" to retrieve current values and identify missing fields, or "update" to set new values. Auto-creates a profile if one does not exist.';
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode(['error' => 'User not authenticated']);
        }

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<string, mixed> $data */
        $data = $request->toArray();

        /** @var string $action */
        $action = $data['action'] ?? 'get';

        return match ($action) {
            'get' => $this->getBiometrics($profile),
            'update' => $this->updateBiometrics($profile, $data),
            default => (string) json_encode(['error' => 'Unknown action: '.$action]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required()
                ->enum(['get', 'update'])
                ->description('Action to perform: "get" to retrieve current biometrics, "update" to set new values.'),
            'age' => $schema->integer()->required()->nullable()
                ->description('User age in years.'),
            'date_of_birth' => $schema->string()->required()->nullable()
                ->description('User date of birth in YYYY-MM-DD format.'),
            'height' => $schema->number()->required()->nullable()
                ->description('User height exactly as the user stated it, in height_unit. Do not convert it yourself.'),
            'height_unit' => $schema->string()->required()->nullable()
                ->enum(HeightUnit::class)
                ->description('Unit of height: "cm" for centimeters or "in" for inches. Turn feet and inches into total inches first (5 ft 10 in = 70). Defaults to cm.'),
            'weight' => $schema->number()->required()->nullable()
                ->description('User weight exactly as the user stated it, in weight_unit. Do not convert it yourself.'),
            'weight_unit' => $schema->string()->required()->nullable()
                ->enum(WeightUnit::class)
                ->description('Unit of weight and target_weight: "kg" for kilograms or "lb" for pounds. Defaults to kg.'),
            'sex' => $schema->string()->required()->nullable()
                ->enum(Sex::class)
                ->description('Biological sex: male, female, or other.'),
            'blood_type' => $schema->string()->required()->nullable()
                ->enum(BloodType::class)
                ->description('Blood type: A+, A-, B+, B-, AB+, AB-, O+, or O-.'),
            'goal_choice' => $schema->string()->required()->nullable()
                ->enum(GoalChoice::class)
                ->description('Primary health goal: spikes, weight_loss, heart_health, build_muscle, or healthy_eating.'),
            'animal_product_choice' => $schema->string()->required()->nullable()
                ->enum(AnimalProductChoice::class)
                ->description('Dietary preference: omnivore, pescatarian, or vegan.'),
            'intensity_choice' => $schema->string()->required()->nullable()
                ->enum(IntensityChoice::class)
                ->description('Plan intensity: balanced or aggressive.'),
            'target_weight' => $schema->number()->required()->nullable()
                ->description('Target weight exactly as the user stated it, in weight_unit. Do not convert it yourself.'),
            'confirm_measurements' => $schema->boolean()->required()->nullable()
                ->description('Set to true only after the user has explicitly confirmed height and weight values that this tool flagged with requires_confirmation.'),
            'additional_goals' => $schema->string()->required()->nullable()
                ->description('Free-text additional health or fitness goals.'),
        ];
    }

    private function getBiometrics(UserProfile $profile): string
    {
        $fields = $this->formatProfile($profile);
        $missing = array_keys(array_filter($fields, fn (mixed $value): bool => $value === null));

        return (string) json_encode([
            'success' => true,
            'biometrics' => $fields,
            'missing_fields' => $missing,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateBiometrics(UserProfile $profile, array $data): string
    {
        /** @var array<string, mixed> $updateData */
        $updateData = [];

        if (isset($data['age']) && is_numeric($data['age'])) {
            $updateData['age'] = (int) $data['age'];
        }

        if (isset($data['date_of_birth']) && is_string($data['date_of_birth'])) {
            try {
                $dateOfBirth = Date::parse($data['date_of_birth']);
                $updateData['date_of_birth'] = $dateOfBirth;
                $updateData['age'] = $dateOfBirth->age;
            } catch (Exception) {
                return (string) json_encode(['error' => 'Invalid date_of_birth format: '.$data['date_of_birth'].'. Use YYYY-MM-DD.']);
            }
        }

        $heightUnit = HeightUnit::tryFrom(is_string($data['height_unit'] ?? null) ? $data['height_unit'] : HeightUnit::Cm->value);
        $weightUnit = WeightUnit::tryFrom(is_string($data['weight_unit'] ?? null) ? $data['weight_unit'] : WeightUnit::Kg->value);

        if (! $heightUnit instanceof HeightUnit) {
            return (string) json_encode(['error' => 'Invalid height_unit value. Use "cm" or "in".']);
        }

        if (! $weightUnit instanceof WeightUnit) {
            return (string) json_encode(['error' => 'Invalid weight_unit value. Use "kg" or "lb".']);
        }

        $statedWeight = isset($data['weight']) && is_numeric($data['weight']) ? (float) $data['weight'] : null;

        if (isset($data['height']) && is_numeric($data['height'])) {
            $updateData['height'] = round($heightUnit->toCentimeters((float) $data['height']), 2);
        }

        if ($statedWeight !== null) {
            $updateData['weight'] = round($weightUnit->toKg($statedWeight), 2);
        }

        if (isset($data['target_weight']) && is_numeric($data['target_weight'])) {
            $updateData['target_weight'] = round($weightUnit->toKg((float) $data['target_weight']), 2);
        }

        if (isset($data['additional_goals']) && is_string($data['additional_goals'])) {
            $updateData['additional_goals'] = $data['additional_goals'];
        }

        if (isset($data['sex']) && is_string($data['sex'])) {
            $sex = Sex::tryFrom($data['sex']);

            if (! $sex) {
                return (string) json_encode(['error' => 'Invalid sex value: '.$data['sex']]);
            }

            $updateData['sex'] = $sex;
        }

        if (isset($data['blood_type']) && is_string($data['blood_type'])) {
            $bloodType = BloodType::tryFrom($data['blood_type']);

            if (! $bloodType) {
                return (string) json_encode(['error' => 'Invalid blood_type value: '.$data['blood_type']]);
            }

            $updateData['blood_type'] = $bloodType;
        }

        if (isset($data['goal_choice']) && is_string($data['goal_choice'])) {
            $goal = GoalChoice::tryFrom($data['goal_choice']);

            if (! $goal) {
                return (string) json_encode(['error' => 'Invalid goal_choice value: '.$data['goal_choice']]);
            }

            $updateData['goal_choice'] = $goal;
        }

        if (isset($data['animal_product_choice']) && is_string($data['animal_product_choice'])) {
            $animal = AnimalProductChoice::tryFrom($data['animal_product_choice']);

            if (! $animal) {
                return (string) json_encode(['error' => 'Invalid animal_product_choice value: '.$data['animal_product_choice']]);
            }

            $updateData['animal_product_choice'] = $animal;
        }

        if (isset($data['intensity_choice']) && is_string($data['intensity_choice'])) {
            $intensity = IntensityChoice::tryFrom($data['intensity_choice']);

            if (! $intensity) {
                return (string) json_encode(['error' => 'Invalid intensity_choice value: '.$data['intensity_choice']]);
            }

            $updateData['intensity_choice'] = $intensity;
        }

        if ($updateData === []) {
            return (string) json_encode(['error' => 'No valid fields provided to update.']);
        }

        $measurementProblem = $this->measurementProblem($profile, $updateData, $statedWeight, $weightUnit, ($data['confirm_measurements'] ?? false) === true);

        if ($measurementProblem !== null) {
            return $measurementProblem;
        }

        if (array_key_exists('height', $updateData) || array_key_exists('weight', $updateData)) {
            $updateData['needs_re_onboarding'] = false;
        }

        $profile->fill($updateData);

        if ($profile->goal_choice instanceof GoalChoice && $profile->isDirty(['goal_choice', 'animal_product_choice', 'intensity_choice'])) {
            $profile->setAttribute('calculated_diet_type', $profile->dietType());
            $profile->setAttribute('derived_activity_multiplier', DietMapper::getActivityMultiplier($profile->goal_choice, $profile->intensity_choice ?? IntensityChoice::Balanced));
        }

        $profile->save();
        $profile->refresh();

        $fields = $this->formatProfile($profile);
        $missing = array_keys(array_filter($fields, fn (mixed $value): bool => $value === null));

        return (string) json_encode([
            'success' => true,
            'message' => 'Biometrics updated successfully.',
            'biometrics' => $fields,
            'missing_fields' => $missing,
        ]);
    }

    /**
     * @param  array<string, mixed>  $updateData
     */
    private function measurementProblem(UserProfile $profile, array $updateData, ?float $statedWeight, WeightUnit $weightUnit, bool $confirmed): ?string
    {
        $height = is_float($updateData['height'] ?? null) ? $updateData['height'] : null;
        $weight = is_float($updateData['weight'] ?? null) ? $updateData['weight'] : null;
        $targetWeight = is_float($updateData['target_weight'] ?? null) ? $updateData['target_weight'] : null;

        if ($height !== null && ! BodyMassIndex::isHeightInRange($height)) {
            return $this->clarification('height', sprintf('Height must be between %d and %d cm (about 1 ft 8 in to 9 ft 10 in).', BodyMassIndex::MINIMUM_HEIGHT_CM, BodyMassIndex::MAXIMUM_HEIGHT_CM));
        }

        if ($weight !== null && ! BodyMassIndex::isWeightInRange($weight)) {
            return $this->clarification('weight', sprintf('Weight must be between %d and %d kg (about 44 to 1,100 lb).', BodyMassIndex::MINIMUM_WEIGHT_KG, BodyMassIndex::MAXIMUM_WEIGHT_KG));
        }

        if ($targetWeight !== null && ! BodyMassIndex::isWeightInRange($targetWeight)) {
            return $this->clarification('target_weight', sprintf('Target weight must be between %d and %d kg (about 44 to 1,100 lb).', BodyMassIndex::MINIMUM_WEIGHT_KG, BodyMassIndex::MAXIMUM_WEIGHT_KG));
        }

        if ($confirmed || ($height === null && $weight === null)) {
            return null;
        }

        $heightCm = $height ?? $profile->height;
        $weightKg = $weight ?? $profile->weight;

        if ($heightCm === null || $weightKg === null || BodyMassIndex::isPlausible($weightKg, $heightCm)) {
            return null;
        }

        $suggestPounds = $statedWeight !== null
            && $weightUnit === WeightUnit::Kg
            && BodyMassIndex::looksLikePounds($weightKg, $heightCm);

        return (string) json_encode([
            'error' => 'These measurements look unusual, so nothing was saved yet.',
            'requires_confirmation' => true,
            'fields' => ['height', 'weight'],
            'message' => $suggestPounds
                ? sprintf('Just checking the units: did you mean %s lb?', $statedWeight)
                : sprintf('Just checking: is a height of %s cm and a weight of %s kg correct?', $heightCm, $weightKg),
            'suggested_weight_unit' => $suggestPounds ? WeightUnit::Lb->value : null,
            'hint' => 'Ask the user to confirm before saving. If they meant pounds, call update_user_biometrics again with weight_unit "lb". If they confirm the values are correct, call it again with confirm_measurements true. Never set confirm_measurements without their explicit confirmation.',
        ]);
    }

    private function clarification(string $field, string $message): string
    {
        return (string) json_encode([
            'error' => $message,
            'requires_clarification' => true,
            'field' => $field,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProfile(UserProfile $profile): array
    {
        return [
            'age' => $profile->age,
            'date_of_birth' => $profile->date_of_birth?->format('Y-m-d'),
            'height' => $profile->height,
            'weight' => $profile->weight,
            'sex' => $profile->sex?->value,
            'blood_type' => $profile->blood_type?->value,
            'goal_choice' => $profile->goal_choice?->value,
            'animal_product_choice' => $profile->animal_product_choice?->value,
            'intensity_choice' => $profile->intensity_choice?->value,
            'target_weight' => $profile->target_weight,
            'additional_goals' => $profile->additional_goals,
            'bmi' => $profile->bmi,
            'bmr' => $profile->bmr,
            'tdee' => $profile->tdee,
        ];
    }
}
