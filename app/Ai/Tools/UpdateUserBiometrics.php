<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\AssessBodyMeasurements;
use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DataSensitivity;
use App\Enums\GoalChoice;
use App\Enums\HeightUnit;
use App\Enums\IntensityChoice;
use App\Enums\MeasurementAssessment;
use App\Enums\Sex;
use App\Enums\WeightUnit;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\BodyMassIndex;
use App\Services\DietMapper;
use BackedEnum;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class UpdateUserBiometrics implements Tool
{
    private const int MINIMUM_AGE = 13;

    private const int MAXIMUM_AGE = 120;

    /**
     * @var array<string, class-string<BackedEnum>>
     */
    private const array CHOICE_FIELDS = [
        'sex' => Sex::class,
        'blood_type' => BloodType::class,
        'goal_choice' => GoalChoice::class,
        'animal_product_choice' => AnimalProductChoice::class,
        'intensity_choice' => IntensityChoice::class,
    ];

    /**
     * @var array<string, string>
     */
    private const array MEASUREMENT_FIELDS = [
        'height' => 'height_unit',
        'weight' => 'weight_unit',
        'target_weight' => 'target_weight_unit',
    ];

    public function __construct(private AssessBodyMeasurements $assessBodyMeasurements) {}

    public function name(): string
    {
        return 'update_user_biometrics';
    }

    public function description(): string
    {
        return 'Get or update user biometric profile fields: age, date_of_birth, height, weight, sex, blood_type, goal_choice, animal_product_choice, intensity_choice, target_weight, and additional_goals. Returned height is in cm and weights are in kg. When updating, pass height and weights exactly as the user stated them with height_unit, weight_unit and target_weight_unit; the tool converts them. If the user did not say a unit and the conversation does not make it clear, ask them before saving. Valid fields are saved even when others need attention; when the result has requires_clarification or requires_confirmation, ask the user about those fields before calling the tool again. Use "get" to retrieve current values and identify missing fields, or "update" to set new values. Auto-creates a profile if one does not exist.';
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
                ->description('User age in years (13 to 120).'),
            'date_of_birth' => $schema->string()->required()->nullable()
                ->description('User date of birth in YYYY-MM-DD format.'),
            'height' => $schema->number()->required()->nullable()
                ->description('User height exactly as the user stated it, in height_unit. Do not convert it yourself.'),
            'height_unit' => $schema->string()->required()->nullable()
                ->enum(HeightUnit::class)
                ->description('Unit of height: "cm", "m", or "in". Turn feet and inches into total inches first (5 ft 10 in = 70). Defaults to cm.'),
            'weight' => $schema->number()->required()->nullable()
                ->description('User weight exactly as the user stated it, in weight_unit. Do not convert it yourself.'),
            'weight_unit' => $schema->string()->required()->nullable()
                ->enum(WeightUnit::class)
                ->description('Unit of weight: "kg" or "lb". Defaults to kg.'),
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
                ->description('Target weight exactly as the user stated it, in target_weight_unit. Do not convert it yourself.'),
            'target_weight_unit' => $schema->string()->required()->nullable()
                ->enum(WeightUnit::class)
                ->description('Unit of target_weight: "kg" or "lb". Defaults to kg.'),
            'confirmation_token' => $schema->string()->required()->nullable()
                ->description('Only after the user explicitly confirms values flagged with requires_confirmation, send the confirmation_token from that result together with the same values. Leave empty otherwise.'),
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
        /** @var list<array{field: string, message: string}> $problems */
        $problems = [];
        /** @var array<string, float> $measurements */
        $measurements = [];
        /** @var array<string, HeightUnit|WeightUnit> $units */
        $units = [];

        if (isset($data['age']) && is_numeric($data['age'])) {
            $age = (int) $data['age'];

            if ($this->isValidAge($age)) {
                $updateData['age'] = $age;
            } else {
                $problems[] = $this->ageProblem('age');
            }
        }

        if (isset($data['date_of_birth']) && is_string($data['date_of_birth'])) {
            $dateOfBirth = $this->parseDate($data['date_of_birth']);

            if (! $dateOfBirth instanceof CarbonInterface) {
                $problems[] = ['field' => 'date_of_birth', 'message' => 'Invalid date_of_birth format: '.$data['date_of_birth'].'. Use YYYY-MM-DD.'];
            } elseif ($dateOfBirth->isFuture() || ! $this->isValidAge((int) $dateOfBirth->age)) {
                $problems[] = $this->ageProblem('date_of_birth');
            } else {
                $updateData['date_of_birth'] = $dateOfBirth;
                $updateData['age'] = (int) $dateOfBirth->age;
            }
        }

        foreach (self::MEASUREMENT_FIELDS as $field => $unitField) {
            if (! isset($data[$field])) {
                continue;
            }

            if (! is_numeric($data[$field])) {
                continue;
            }

            $unit = $field === 'height' ? $this->heightUnit($data[$unitField] ?? null) : $this->weightUnit($data[$unitField] ?? null);

            if ($unit === null) {
                $problems[] = ['field' => $unitField, 'message' => $field === 'height' ? 'Unknown height_unit. Use "cm", "m", or "in".' : sprintf('Unknown %s. Use "kg" or "lb".', $unitField)];

                continue;
            }

            $value = round($unit instanceof HeightUnit ? $unit->toCentimeters((float) $data[$field]) : $unit->toKg((float) $data[$field]), 2);

            if (! ($field === 'height' ? BodyMassIndex::isHeightInRange($value) : BodyMassIndex::isWeightInRange($value))) {
                $problems[] = ['field' => $field, 'message' => $this->rangeMessage($field)];

                continue;
            }

            $measurements[$field] = $value;
            $units[$field] = $unit;
        }

        if (isset($data['additional_goals']) && is_string($data['additional_goals'])) {
            $updateData['additional_goals'] = $data['additional_goals'];
        }

        foreach (self::CHOICE_FIELDS as $field => $enumClass) {
            if (! isset($data[$field])) {
                continue;
            }

            if (! is_string($data[$field])) {
                continue;
            }

            $choice = $enumClass::tryFrom($data[$field]);

            if ($choice instanceof BackedEnum) {
                $updateData[$field] = $choice;
            } else {
                $problems[] = ['field' => $field, 'message' => sprintf('Invalid %s value: %s', $field, $data[$field])];
            }
        }

        $confirmations = $this->confirmations($profile, $measurements, $units);
        $confirmationToken = null;

        if ($confirmations !== []) {
            $heldBack = array_values(array_unique(array_merge(...array_column($confirmations, 'fields'))));
            $confirmationToken = $this->confirmationToken($profile, array_intersect_key($measurements, array_flip($heldBack)));

            if (is_string($data['confirmation_token'] ?? null) && hash_equals($confirmationToken, $data['confirmation_token'])) {
                $confirmations = [];
                $confirmationToken = null;
            } else {
                $measurements = array_diff_key($measurements, array_flip($heldBack));
            }
        }

        $updateData = [...$updateData, ...$measurements];

        if ($updateData === [] && $problems === [] && $confirmations === []) {
            return (string) json_encode(['error' => 'No valid fields provided to update.']);
        }

        if ($updateData !== []) {
            $this->save($profile, $updateData);
        }

        if ($problems === [] && $confirmations === []) {
            $fields = $this->formatProfile($profile);
            $missing = array_keys(array_filter($fields, fn (mixed $value): bool => $value === null));

            return (string) json_encode([
                'success' => true,
                'message' => 'Biometrics updated successfully.',
                'biometrics' => $fields,
                'missing_fields' => $missing,
            ]);
        }

        return $this->attentionResponse($profile, array_keys($updateData), $problems, $confirmations, $confirmationToken);
    }

    /**
     * @param  array<string, mixed>  $updateData
     */
    private function save(UserProfile $profile, array $updateData): void
    {
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
    }

    /**
     * @param  array<string, float>  $measurements
     * @param  array<string, HeightUnit|WeightUnit>  $units
     * @return list<array{fields: list<string>, message: string, suggestion: array<string, float|string>|null}>
     */
    private function confirmations(UserProfile $profile, array $measurements, array $units): array
    {
        $heightCm = $measurements['height'] ?? $profile->height;

        if ($heightCm === null) {
            return [];
        }

        $confirmations = [];
        $weightKg = $measurements['weight'] ?? $profile->weight;
        $changedFields = array_values(array_intersect(['height', 'weight'], array_keys($measurements)));

        if ($changedFields !== [] && $weightKg !== null) {
            $assessment = $this->assessBodyMeasurements->handle($weightKg, $heightCm, $profile);

            if ($assessment !== MeasurementAssessment::Plausible) {
                $confirmations[] = $this->measurementConfirmation(
                    $assessment,
                    $changedFields,
                    $weightKg,
                    $heightCm,
                    ($units['weight'] ?? WeightUnit::Kg) === WeightUnit::Kg,
                    ($units['height'] ?? HeightUnit::Cm) === HeightUnit::Cm,
                );
            }
        }

        $targetWeightKg = $measurements['target_weight'] ?? null;

        if ($targetWeightKg !== null && $targetWeightKg !== $profile->target_weight) {
            $assessment = $this->assessBodyMeasurements->handle($targetWeightKg, $heightCm);

            if ($assessment !== MeasurementAssessment::Plausible) {
                $suggestPounds = $assessment === MeasurementAssessment::LooksLikePounds
                    && ($units['target_weight'] ?? WeightUnit::Kg) === WeightUnit::Kg;

                $confirmations[] = [
                    'fields' => ['target_weight'],
                    'message' => $suggestPounds
                        ? sprintf('Just checking the units: did you mean a target weight of %s lb?', $targetWeightKg)
                        : sprintf('Just checking: is a target weight of %s kg right for a height of %s cm?', $targetWeightKg, $heightCm),
                    'suggestion' => $suggestPounds ? ['target_weight' => $targetWeightKg, 'target_weight_unit' => WeightUnit::Lb->value] : null,
                ];
            }
        }

        return $confirmations;
    }

    /**
     * @param  list<string>  $fields
     * @return array{fields: list<string>, message: string, suggestion: array<string, float|string>|null}
     */
    private function measurementConfirmation(MeasurementAssessment $assessment, array $fields, float $weightKg, float $heightCm, bool $weightReadAsKilograms, bool $heightReadAsCentimeters): array
    {
        if ($assessment === MeasurementAssessment::LooksLikePounds && $weightReadAsKilograms) {
            return [
                'fields' => $fields,
                'message' => sprintf('Just checking the units: did you mean a weight of %s lb?', $weightKg),
                'suggestion' => ['weight' => $weightKg, 'weight_unit' => WeightUnit::Lb->value],
            ];
        }

        if ($assessment === MeasurementAssessment::LooksLikeInches && $heightReadAsCentimeters) {
            $inches = (int) round($heightCm);

            return [
                'fields' => $fields,
                'message' => sprintf('Just checking the units: did you mean a height of %s in (%s)?', $heightCm, $this->feetAndInches($inches)),
                'suggestion' => ['height' => $heightCm, 'height_unit' => HeightUnit::In->value],
            ];
        }

        return [
            'fields' => $fields,
            'message' => sprintf('Just checking: is a height of %s cm and a weight of %s kg correct?', $heightCm, $weightKg),
            'suggestion' => null,
        ];
    }

    /**
     * @param  list<string>  $savedFields
     * @param  list<array{field: string, message: string}>  $problems
     * @param  list<array{fields: list<string>, message: string, suggestion: array<string, float|string>|null}>  $confirmations
     */
    private function attentionResponse(UserProfile $profile, array $savedFields, array $problems, array $confirmations, ?string $confirmationToken): string
    {
        $response = [
            'error' => implode(' ', [...array_column($problems, 'message'), ...array_column($confirmations, 'message')]),
            'saved_fields' => $savedFields,
        ];

        if ($problems !== []) {
            $response['requires_clarification'] = true;
            $response['field'] = $problems[0]['field'];
            $response['problems'] = $problems;
        }

        if ($confirmations !== []) {
            $response['requires_confirmation'] = true;
            $response['confirmations'] = $confirmations;
            $response['confirmation_token'] = $confirmationToken;
            $response['hint'] = 'Ask the user about each confirmation before saving those values. If they meant a suggested unit, call update_user_biometrics again with the suggestion. If they confirm the values as stated, call it again with the same values and this confirmation_token. Never send the token without their explicit confirmation.';
        }

        $response['biometrics'] = $this->formatProfile($profile);

        return (string) json_encode($response);
    }

    /**
     * @param  array<string, float>  $values
     */
    private function confirmationToken(UserProfile $profile, array $values): string
    {
        ksort($values);

        return mb_substr(hash_hmac('sha256', (string) json_encode([$profile->user_id, $values]), config()->string('app.key')), 0, 16);
    }

    private function heightUnit(mixed $value): ?HeightUnit
    {
        return $value === null || $value === '' ? HeightUnit::Cm : HeightUnit::fromInput($value);
    }

    private function weightUnit(mixed $value): ?WeightUnit
    {
        return $value === null || $value === '' ? WeightUnit::Kg : WeightUnit::fromInput($value);
    }

    private function rangeMessage(string $field): string
    {
        if ($field === 'height') {
            $inches = BodyMassIndex::heightRangeInInches();

            return sprintf(
                'Height must be between %d and %d cm (about %s to %s).',
                BodyMassIndex::MINIMUM_HEIGHT_CM,
                BodyMassIndex::MAXIMUM_HEIGHT_CM,
                $this->feetAndInches($inches['min']),
                $this->feetAndInches($inches['max']),
            );
        }

        $pounds = BodyMassIndex::weightRangeInPounds();

        return sprintf(
            '%s must be between %d and %d kg (about %d to %s lb).',
            $field === 'target_weight' ? 'Target weight' : 'Weight',
            BodyMassIndex::MINIMUM_WEIGHT_KG,
            BodyMassIndex::MAXIMUM_WEIGHT_KG,
            $pounds['min'],
            number_format($pounds['max']),
        );
    }

    private function feetAndInches(int $inches): string
    {
        return sprintf('%d ft %d in', intdiv($inches, 12), $inches % 12);
    }

    private function isValidAge(int $age): bool
    {
        return $age >= self::MINIMUM_AGE && $age <= self::MAXIMUM_AGE;
    }

    /**
     * @return array{field: string, message: string}
     */
    private function ageProblem(string $field): array
    {
        return [
            'field' => $field,
            'message' => sprintf(
                $field === 'age' ? 'Age must be between %d and %d.' : 'date_of_birth must give an age between %d and %d.',
                self::MINIMUM_AGE,
                self::MAXIMUM_AGE,
            ),
        ];
    }

    private function parseDate(string $value): ?CarbonInterface
    {
        try {
            return Date::parse($value);
        } catch (Exception) {
            return null;
        }
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
