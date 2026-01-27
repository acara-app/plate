<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Ai\BaseAgent;
use App\Ai\MealPlanPromptBuilder;
use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanData;
use App\DataObjects\PreviousDayContext;
use App\Enums\DietType;
use App\Enums\SettingKey;
use App\Models\MealPlan;
use App\Models\Setting;
use App\Models\User;
use App\Services\SystemPromptProviderResolver;
use App\Utilities\JsonCleaner;
use App\Workflows\MealPlanInitializeWorkflow;
use Prism\Prism\ValueObjects\ProviderTool;
use Workflow\WorkflowStub;

final class MealPlanGeneratorAgent extends BaseAgent
{
    private ?DietType $dietType = null;

    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
        private readonly SystemPromptProviderResolver $systemPromptResolver,
    ) {}

    /**
     * Set the diet type for this agent.
     * This allows for diet-specific system prompts.
     */
    public function withDietType(DietType $dietType): self
    {
        $this->dietType = $dietType;

        return $this;
    }

    public function systemPrompt(): string
    {
        $dietType = $this->dietType ?? DietType::Balanced;

        return $this->systemPromptResolver->resolve($dietType)->run();
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
