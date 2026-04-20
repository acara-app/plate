<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Contracts\Ai\GeneratesMealPlans;
use App\Models\User;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class CreateMealPlan implements Tool
{
    private const int MIN_DAYS = 1;

    private const int MAX_DAYS = 7;

    private const int DEFAULT_DAYS = 7;

    public function name(): string
    {
        return 'create_meal_plan';
    }

    public function description(): string
    {
        return 'Generate a complete multi-day meal plan tailored to the user\'s profile, dietary preferences, health conditions, and goals. This creates a structured meal plan that can be saved and followed. Use this when the user explicitly asks for a meal plan or when in "Generate Meal Plan" mode. If the user does not specify a day count, default to 7 days.';
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'meal_plan' => null,
            ]);
        }

        $totalDaysValue = $request['total_days'] ?? self::DEFAULT_DAYS;
        $requestedDays = is_numeric($totalDaysValue) ? (int) $totalDaysValue : self::DEFAULT_DAYS;
        $totalDays = max(self::MIN_DAYS, min($requestedDays, self::MAX_DAYS));
        $wasCapped = $requestedDays !== $totalDays;
        /** @var string|null $customPrompt */
        $customPrompt = $request['custom_prompt'] ?? null;

        try {
            resolve(GeneratesMealPlans::class)->handle($user, $totalDays, $customPrompt);

            $mealPlansUrl = route('meal-plans.index');

            return (string) json_encode([
                'success' => true,
                'message' => sprintf('Your %d-day meal plan is now being generated. View it here: [Meal Plans](%s) %s', $totalDays, $mealPlansUrl, $mealPlansUrl),
                'total_days' => $totalDays,
                'requested_days' => $requestedDays,
                'was_capped' => $wasCapped,
                'max_allowed_days' => self::MAX_DAYS,
                'custom_prompt' => $customPrompt,
                'redirect_url' => $mealPlansUrl,
            ]);
        } catch (Exception $exception) {
            return (string) json_encode([
                'error' => 'Failed to start meal plan generation: '.$exception->getMessage(),
                'meal_plan' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'total_days' => $schema->integer()
                ->description('Number of days for the meal plan (minimum: 1, maximum: 7, default: 7 when the user does not specify). If user requests more than 7, the system caps it to 7.')
                ->required(),
            'custom_prompt' => $schema->string()->required()->nullable()
                ->description('Optional custom instructions or preferences for the meal plan (e.g., "focus on Mediterranean diet", "high protein for muscle building")'),
        ];
    }
}
