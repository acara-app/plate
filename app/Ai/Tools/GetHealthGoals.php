<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Contracts\Actions\GetsUserProfileContext;
use App\Enums\DataSensitivity;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Personal)]
final readonly class GetHealthGoals implements Tool
{
    public function name(): string
    {
        return 'get_health_goals';
    }

    public function description(): string
    {
        return "Retrieve the current user's health and wellness goals. Use this to understand what the user wants to achieve regarding their overall health, energy levels, stress management, sleep quality, or other wellness objectives.";
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return json_encode([
                'error' => 'User not authenticated',
                'goals' => null,
            ]) ?: '{"error":"User not authenticated","goals":null}';
        }

        $context = resolve(GetsUserProfileContext::class)->handle($user);

        /** @var array<string, mixed>|null $rawData */
        $rawData = $context['raw_data'];

        if ($rawData === null) {
            return json_encode([
                'success' => false,
                'message' => 'User has not completed their profile',
                'goals' => null,
                'suggestion' => 'Ask the user to complete their profile for personalized goal tracking',
            ]) ?: '{"success":false,"message":"User has not completed their profile","goals":null}';
        }

        /** @var array<string, mixed> $goals */
        $goals = $rawData['goals'] ?? [];

        return json_encode([
            'success' => true,
            'goals' => [
                'primary_goal' => $goals['primary_goal'] ?? null,
                'target_weight_kg' => $goals['target_weight_kg'] ?? null,
                'intensity' => $goals['intensity'] ?? null,
                'additional_goals' => $goals['additional_goals'] ?? null,
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
        return [
            'context' => $schema->string()->required()->nullable()
                ->description('Optional context for why the health goals are being retrieved (e.g., "meal suggestion", "health assessment").'),
        ];
    }
}
