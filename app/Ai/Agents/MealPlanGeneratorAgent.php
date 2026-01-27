<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Ai\BaseAgent;
use App\Ai\MealPlanPromptBuilder;
use App\Ai\SystemPrompt;
use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanData;
use App\DataObjects\PreviousDayContext;
use App\Enums\SettingKey;
use App\Models\MealPlan;
use App\Models\Setting;
use App\Models\User;
use App\Utilities\JsonCleaner;
use App\Workflows\MealPlanInitializeWorkflow;
use Prism\Prism\ValueObjects\ProviderTool;
use Workflow\WorkflowStub;

final class MealPlanGeneratorAgent extends BaseAgent
{
    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
    ) {}

    public function systemPrompt(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist with access to the USDA FoodData Central database.',
                'Prioritize whole, minimally processed foods.',
                'Ensure all calculations are accurate and based on USDA data.',
                'Respect the user\'s calculated diet type and macronutrient targets.',
            ],
            steps: [
                '1. Search the database for appropriate whole foods that match the user\'s dietary needs and diet type',
                '2. For each ingredient, retrieve its USDA nutrition values per 100g (protein, carbs, fat, calories)',
                '3. Create a meal plan using ONLY ingredients found in the database',
                '4. Calculate exact nutritional values based on ingredient quantities and USDA data per 100g',
                '5. Ensure meals align with the specified diet type (e.g., Keto, Mediterranean, Paleo, etc.)',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'The JSON must be parseable by json_decode()',
                'Use double quotes for all strings',
                'Ensure all brackets and braces are properly closed',
            ],
            toolsUsage: [
                'Use the file_search tool to find USDA nutritional data for ingredients',
            ],
        );
    }

    public function maxTokens(): int
    {
        return 64000;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [
            'timeout' => 180,
        ];
    }

    /**
     * @return array<int, ProviderTool>
     */
    public function providerTools(): array
    {
        $storeNames = $this->getFileSearchStoreNames();

        if ($storeNames === []) {
            return [];
        }

        return [
            new ProviderTool(
                type: 'file_search',
                name: 'file_search',
                options: [
                    'file_search_store_names' => $storeNames,
                ]
            ),
        ];
    }

    public function handle(User $user, int $totalDays = 7): void
    {
        $glucoseAnalysis = $this->analyzeGlucose->handle($user);

        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, $totalDays);

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, $mealPlan, $glucoseAnalysis->analysisData);
    }

    /**
     * Generate a complete multi-day meal plan.
     */
    public function generate(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): MealPlanData
    {
        $prompt = $this->promptBuilder->handle($user, $glucoseAnalysis);

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
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
    ): DayMealsData {
        /** @var string|null $customPrompt */
        $customPrompt = $mealPlan?->metadata['custom_prompt'] ?? null;

        $prompt = $this->promptBuilder->handleForDay(
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
            $glucoseAnalysis,
            $customPrompt,
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
        $response = $this->text()
            ->withPrompt($prompt)
            ->withProviderTools($this->providerTools())
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
