<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\AnimalProductChoice;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class UpdateUserBiometrics implements Tool
{
    public function name(): string
    {
        return 'update_user_biometrics';
    }

    public function description(): string
    {
        return 'Get or update user biometric profile fields: age, height (cm), weight (kg), sex, goal_choice, animal_product_choice, intensity_choice, target_weight, and additional_goals. Use "get" to retrieve current values and identify missing fields, or "update" to set new values. Auto-creates a profile if one does not exist.';
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
            'height' => $schema->number()->required()->nullable()
                ->description('User height in centimeters.'),
            'weight' => $schema->number()->required()->nullable()
                ->description('User weight in kilograms.'),
            'sex' => $schema->string()->required()->nullable()
                ->enum(Sex::class)
                ->description('Biological sex: male, female, or other.'),
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
                ->description('Target weight in kilograms.'),
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

        if (isset($data['height']) && is_numeric($data['height'])) {
            $updateData['height'] = (float) $data['height'];
        }

        if (isset($data['weight']) && is_numeric($data['weight'])) {
            $updateData['weight'] = (float) $data['weight'];
        }

        if (isset($data['target_weight']) && is_numeric($data['target_weight'])) {
            $updateData['target_weight'] = (float) $data['target_weight'];
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

        $profile->update($updateData);
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
     * @return array<string, mixed>
     */
    private function formatProfile(UserProfile $profile): array
    {
        return [
            'age' => $profile->age,
            'height' => $profile->height,
            'weight' => $profile->weight,
            'sex' => $profile->sex?->value,
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
