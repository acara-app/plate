<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Ai\MealPlanPromptBuilder;
use App\Ai\Tools\GetDietReference;
use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanData;
use App\DataObjects\PreviousDayContext;
use App\Enums\DietType;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\SystemPromptProviderResolver;
use App\Utilities\JsonCleaner;
use App\Workflows\MealPlanInitializeWorkflow;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Workflow\WorkflowStub;

final class MealPlanAgent implements Agent, HasTools
{
    use Promptable;

    private ?DietType $dietType = null;

    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
        private readonly SystemPromptProviderResolver $systemPromptResolver,
    ) {}

    public function withDietType(DietType $dietType): self
    {
        $this->dietType = $dietType;

        return $this;
    }

    public function instructions(): string
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
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [
            new GetDietReference,
        ];
    }

    public function handle(User $user, int $totalDays = 7): void
    {
        $glucoseAnalysis = $this->analyzeGlucose->handle($user);

        $dietType = $user->profile->calculated_diet_type ?? DietType::Balanced;

        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, $totalDays, $dietType);

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, $mealPlan, $glucoseAnalysis->analysisData);
    }

    /**
     * Generate a complete multi-day meal plan.
     */
    public function generate(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): MealPlanData
    {
        $prompt = $this->promptBuilder->handle($user, $glucoseAnalysis);

        $response = $this->prompt($prompt);

        $cleanedJsonText = JsonCleaner::extractAndValidateJson((string) $response);

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

        $response = $this->prompt($prompt);

        $cleanedJsonText = JsonCleaner::extractAndValidateJson((string) $response);

        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return DayMealsData::from($data);
    }
}
