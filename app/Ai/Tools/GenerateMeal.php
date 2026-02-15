<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\MealGeneratorAgent;
use App\Models\User;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GenerateMeal implements Tool
{
    public function __construct(
        private MealGeneratorAgent $mealGenerator,
    ) {}

    public function description(): string
    {
        return 'Generate a personalized meal suggestion based on user preferences, dietary restrictions, and nutritional goals. Returns a complete meal with nutritional information.';
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'meal' => null,
            ]);
        }

        /** @var string $mealType */
        $mealType = $request['meal_type'] ?? 'any';
        /** @var string|null $cuisine */
        $cuisine = $request['cuisine'] ?? null;
        /** @var int|null $maxCalories */
        $maxCalories = $request['max_calories'] ?? null;
        /** @var string|null $specificRequest */
        $specificRequest = $request['specific_request'] ?? null;

        try {
            $meal = $this->mealGenerator->generate(
                $user,
                $mealType,
                $cuisine,
                $maxCalories,
                $specificRequest,
            );

            return (string) json_encode([
                'success' => true,
                'meal' => $meal,
            ]);
        } catch (Exception $e) {
            return (string) json_encode([
                'error' => 'Failed to generate meal: '.$e->getMessage(),
                'meal' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'meal_type' => $schema->string()
                ->enum(['breakfast', 'lunch', 'dinner', 'snack', 'any'])
                ->description('The type of meal to generate')
                ->required(),
            'cuisine' => $schema->string()
                ->description('Preferred cuisine style (e.g., Mediterranean, Asian, Mexican, Italian)'),
            'max_calories' => $schema->integer()
                ->description('Maximum calories for the meal'),
            'specific_request' => $schema->string()
                ->description('Any specific requirements or preferences (e.g., "high protein", "quick to prepare", "comfort food")'),
        ];
    }
}
