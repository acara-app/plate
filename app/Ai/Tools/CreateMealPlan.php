<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\MealPlanAgent;
use App\Models\User;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class CreateMealPlan implements Tool
{
    public function __construct(
        private MealPlanAgent $mealPlanAgent,
    ) {
    }

    public function name(): string
    {
        return 'create_meal_plan';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Generate a complete multi-day meal plan tailored to the user\'s profile, dietary preferences, health conditions, and goals. This creates a structured meal plan that can be saved and followed. Use this when the user explicitly asks for a meal plan or when in "Generate Meal Plan" mode.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'meal_plan' => null,
            ]);
        }

        $totalDaysValue = $request['total_days'] ?? 1;
        /** @var int $totalDays */
        $totalDays = min(is_numeric($totalDaysValue) ? (int) $totalDaysValue : 1, 7);
        /** @var string|null $customPrompt */
        $customPrompt = $request['custom_prompt'] ?? null;

        try {
            // Start the meal plan generation workflow
            $this->mealPlanAgent->handle($user, $totalDays);

            return (string) json_encode([
                'success' => true,
                'message' => "I've started generating your {$totalDays}-day meal plan! You can view it in your [Meal Plans](/meal-plans) section once it's ready.",
                'total_days' => $totalDays,
                'custom_prompt' => $customPrompt,
                'redirect_url' => '/meal-plans',
            ]);
        } catch (Exception $e) {
            return (string) json_encode([
                'error' => 'Failed to start meal plan generation: '.$e->getMessage(),
                'meal_plan' => null,
            ]);
        }
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'total_days' => $schema->integer()
                ->description('Number of days for the meal plan (default: 1, max: 7)')
                ->required(),
            'custom_prompt' => $schema->string()
                ->description('Optional custom instructions or preferences for the meal plan (e.g., "focus on Mediterranean diet", "high protein for muscle building")'),
        ];
    }
}
