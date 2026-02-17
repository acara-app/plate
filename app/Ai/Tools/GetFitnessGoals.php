<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Contracts\Actions\GetsUserProfileContext;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GetFitnessGoals implements Tool
{
    public function __construct(
        private GetsUserProfileContext $profileContext,
    ) {}

    public function name(): string
    {
        return 'get_fitness_goals';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Retrieve the current user\'s fitness and exercise goals. Use this to understand what the user wants to achieve regarding their fitness, strength, endurance, weight management, or other athletic objectives.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return json_encode([
                'error' => 'User not authenticated',
                'goals' => null,
            ]) ?: '{"error":"User not authenticated","goals":null}';
        }

        $context = $this->profileContext->handle($user);

        /** @var array<string, mixed>|null $rawData */
        $rawData = $context['raw_data'];

        if ($rawData === null) {
            return json_encode([
                'success' => false,
                'message' => 'User has not completed their profile',
                'goals' => null,
                'suggestion' => 'Ask the user to complete their profile for personalized fitness goal tracking',
            ]) ?: '{"success":false,"message":"User has not completed their profile","goals":null}';
        }

        /** @var array<string, mixed> $goals */
        $goals = $rawData['goals'] ?? [];
        /** @var array<string, mixed> $biometrics */
        $biometrics = $rawData['biometrics'] ?? [];

        return json_encode([
            'success' => true,
            'goals' => [
                'primary_goal' => $goals['primary_goal'] ?? null,
                'target_weight_kg' => $goals['target_weight_kg'] ?? null,
                'intensity' => $goals['intensity'] ?? null,
                'additional_goals' => $goals['additional_goals'] ?? null,
            ],
            'biometrics' => [
                'weight_kg' => $biometrics['weight_kg'] ?? null,
                'height_cm' => $biometrics['height_cm'] ?? null,
                'bmi' => $biometrics['bmi'] ?? null,
                'tdee' => $biometrics['tdee'] ?? null,
            ],
            'onboarding_completed' => $context['onboarding_completed'],
            'missing_data' => $context['missing_data'],
        ]) ?: '{"success":true}';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
