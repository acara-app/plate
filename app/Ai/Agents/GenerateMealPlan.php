<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\DataObjects\DayMealsData;
use App\DataObjects\MealPlanData;
use App\DataObjects\PreviousDayContext;
use App\Enums\SettingKey;
use App\Models\Setting;
use App\Models\User;
use App\Utilities\JsonCleaner;
use App\Workflows\GenerateMealPlanWorkflow;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\ProviderTool;
use Workflow\WorkflowStub;

final readonly class GenerateMealPlan
{
    public function __construct(
        private CreateMealPlanPrompt $createPrompt,
    ) {}

    public function handle(User $user): void
    {
        WorkflowStub::make(GenerateMealPlanWorkflow::class)
            ->start($user, totalDays: 7);
    }

    /**
     * Generate a complete multi-day meal plan (legacy method).
     */
    public function generate(User $user): MealPlanData
    {
        $prompt = $this->createPrompt->handle($user);

        $jsonText = $this->generateMealPlanJson($prompt);

        $cleanedJsonText = JsonCleaner::extractAndValidateJson($jsonText);

        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return MealPlanData::from($data);
    }

    /**
     * Generate meals for a single day.
     */
    public function generateForDay(
        User $user,
        int $dayNumber,
        int $totalDays = 7,
        ?PreviousDayContext $previousDaysContext = null,
    ): DayMealsData {
        $prompt = $this->createPrompt->handleForDay(
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
        );

        $jsonText = $this->generateMealPlanJson($prompt);

        $cleanedJsonText = JsonCleaner::extractAndValidateJson($jsonText);

        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return DayMealsData::from($data);
    }

    /**
     * Generate meal plan as JSON using File Search for accurate USDA nutritional data
     */
    private function generateMealPlanJson(string $prompt): string
    {
        $storeNames = $this->getFileSearchStoreNames();

        $providerTools = [];
        if ($storeNames !== []) {
            $providerTools[] = new ProviderTool(
                type: 'file_search',
                name: 'file_search',
                options: [
                    'file_search_store_names' => $storeNames,
                ]
            );
        }

        $response = Prism::text()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withSystemPrompt(
                'You are an expert nutritionist with access to the USDA FoodData Central database.\n\n'
                .'Your task:\n'
                .'1. Search the database for appropriate whole foods that match the user\'s dietary needs\n'
                .'2. For each ingredient, retrieve its USDA nutrition values per 100g (protein, carbs, fat, calories)\n'
                .'3. Create a meal plan using ONLY ingredients found in the database\n'
                .'4. Calculate exact nutritional values based on ingredient quantities and USDA data per 100g\n\n'
                .'CRITICAL JSON FORMAT REQUIREMENTS:\n'
                .'- Your response MUST be valid JSON and ONLY JSON\n'
                .'- Start your response with { and end with }\n'
                .'- Do NOT include markdown code blocks (no ```json)\n'
                .'- Do NOT include explanatory text before or after the JSON\n'
                .'- The JSON must be parseable by json_decode()\n'
                .'- Use double quotes for all strings\n'
                .'- Ensure all brackets and braces are properly closed\n\n'
                .'Prioritize whole, minimally processed foods. Ensure all calculations are accurate and based on USDA data.'
            )
            ->withPrompt($prompt)
            ->withMaxTokens(64000)
            ->withClientOptions([
                'timeout' => 180,
            ])
            ->withProviderTools($providerTools)
            ->asText();

        return $response->text;
    }

    /**
     * @return array<int, string>
     */
    private function getFileSearchStoreNames(): array
    {
        $storeName = Setting::get(SettingKey::GeminiFileSearchStoreName);

        if (! $storeName || ! is_string($storeName)) {
            return [];
        }

        return [$storeName];
    }
}
