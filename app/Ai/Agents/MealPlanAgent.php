<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Ai\MealPlanPromptBuilder;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Data\DayMealsData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
use App\Enums\MealType;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\SystemPromptProviderResolver;
use App\Services\ToolRegistry;
use App\Workflows\MealPlanInitializeWorkflow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Workflow\WorkflowStub;

#[MaxTokens(64000)]
#[Timeout(180)]
final class MealPlanAgent implements Agent, GeneratesMealPlans, HasStructuredOutput, HasTools
{
    use Promptable;

    private ?DietType $dietType = null;

    /** @phpstan-ignore property.onlyWritten */
    private ?User $user = null;

    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
        private readonly SystemPromptProviderResolver $systemPromptResolver,
        private readonly ToolRegistry $toolRegistry,
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

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function tools(): array
    {
        return $this->toolRegistry->getMealPlanTools();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $ingredientSchema = new ObjectType([
            'name' => $schema->string()->required(),
            'quantity' => $schema->string()->required(),
            'specificity' => $schema->string()->nullable(),
            'barcode' => $schema->string()->nullable(),
        ])->withoutAdditionalProperties();

        $mealSchema = new ObjectType([
            'type' => $schema->string()->enum(MealType::class)->required(),
            'name' => $schema->string()->required(),
            'description' => $schema->string()->nullable()->required(),
            'preparation_instructions' => $schema->string()->nullable()->required(),
            'ingredients' => (new ArrayType)->items($ingredientSchema)->nullable()->required(),
            'portion_size' => $schema->string()->nullable()->required(),
            'calories' => $schema->number()->required(),
            'protein_grams' => $schema->number()->nullable()->required(),
            'carbs_grams' => $schema->number()->nullable()->required(),
            'fat_grams' => $schema->number()->nullable()->required(),
            'preparation_time_minutes' => $schema->integer()->nullable()->required(),
            'sort_order' => $schema->integer()->required(),
        ])->withoutAdditionalProperties();

        $metadataSchema = new ObjectType([
            'preparation_notes' => $schema->string()->nullable(),
        ])->withoutAdditionalProperties();

        return [
            'meals' => (new ArrayType)->items($mealSchema)->required(),
            'metadata' => $metadataSchema->nullable(),
        ];
    }

    public function handle(User $user, int $totalDays = 7, ?string $customPrompt = null): void
    {
        $glucoseAnalysis = $this->analyzeGlucose->handle($user);

        $dietType = $user->profile->calculated_diet_type ?? DietType::Balanced;

        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, $totalDays, $dietType);

        if ($customPrompt !== null && $customPrompt !== '') {
            $mealPlan->update([
                'metadata->custom_prompt' => $customPrompt,
            ]);
        }

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, $mealPlan, $glucoseAnalysis->analysisData, $dietType);
    }

    public function generateForDay(
        User $user,
        int $dayNumber,
        int $totalDays = 7,
        ?PreviousDayContext $previousDaysContext = null,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
    ): DayMealsData {
        $this->user = $user;

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

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        return DayMealsData::from($response->toArray());
    }
}
