<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Actions\CreateMealPlan;
use App\Ai\Concerns\HasUsageUser;
use App\Ai\Concerns\ReasonsWithMediumEffort;
use App\Ai\MealPlanPromptBuilder;
use App\Contracts\Ai\BillsUsageToUser;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Data\DayMealsData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
use App\Enums\MealType;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\ToolRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\ToolChoice;
use RuntimeException;

#[MaxSteps(3)]
#[MaxTokens(64000)]
#[Timeout(180)]
#[ToolChoice(ToolChoice::tool, 'LoadSkill')]
final class MealPlanAgent implements Agent, BillsUsageToUser, GeneratesMealPlans, HasProviderOptions, HasSkills, HasStructuredOutput, HasTools
{
    use HasUsageUser;
    use Promptable;
    use ReasonsWithMediumEffort;

    private const string MEDITERRANEAN_SCORE_CARD = 'references/med-diet-nutrient-score-card.md';

    private DietType $dietType = DietType::Balanced;

    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function withDietType(DietType $dietType): self
    {
        $this->dietType = $dietType;

        return $this;
    }

    public function instructions(): string
    {
        $name = $this->dietType->skillName();

        return <<<PROMPT
            Create one day of meals for the selected diet. First call LoadSkill with name "{$name}" and omit the path to read its instructions.
            Follow the guide and load any references it requires before choosing meals. Respect the user's allergies, medical restrictions and preferences.
            Return the meals using the requested schema, with ingredient quantities, portions and estimated nutrition values.
            PROMPT;
    }

    /** @return list<Skill> */
    public function skills(): array
    {
        $name = $this->dietType->skillName();
        $skill = Skill::fromDirectory(resource_path('skills/diets/'.$name));

        if ($skill?->name !== $name || blank($skill->instructions)) {
            throw new RuntimeException("Missing or invalid meal planning skill [{$name}].");
        }

        return [$skill];
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function tools(): array
    {
        return $this->toolRegistry->getSharedTools();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $ingredientSchema = new ObjectType([
            'name' => $schema->string()->required(),
            'quantity' => $schema->string()->required(),
            'specificity' => $schema->string()->nullable()->required(),
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
            'preparation_notes' => $schema->string()->nullable()->required(),
        ])->withoutAdditionalProperties();

        return [
            'meals' => (new ArrayType)->items($mealSchema)->required(),
            'metadata' => $metadataSchema->nullable()->required(),
        ];
    }

    public function handle(User $user, int $totalDays = 7, ?string $customPrompt = null): void
    {
        $glucoseAnalysis = $this->analyzeGlucose->handle($user);

        $dietType = $user->profile->calculated_diet_type ?? DietType::Balanced;

        $mealPlan = resolve(CreateMealPlan::class)->handle($user, $totalDays, $dietType);

        if ($customPrompt !== null && $customPrompt !== '') {
            $mealPlan->update([
                'metadata->custom_prompt' => $customPrompt,
            ]);
        }

        dispatch(new GenerateInitialMealPlanJob($user, $mealPlan, $glucoseAnalysis->analysisData, $dietType));
    }

    public function generateForDay(
        User $user,
        int $dayNumber,
        int $totalDays = 7,
        ?PreviousDayContext $previousDaysContext = null,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
    ): DayMealsData {
        $this->setModelUser($user);

        /** @var string|null $customPrompt */
        $customPrompt = $mealPlan?->metadata['custom_prompt'] ?? null;

        $prompt = $this->promptBuilder->handleForDay(
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
            $glucoseAnalysis,
            $customPrompt,
            $this->dietType,
        );

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        $this->verifySkillReads($response);

        return DayMealsData::from($response->toArray());
    }

    private function verifySkillReads(StructuredAgentResponse $response): void
    {
        $name = $this->dietType->skillName();
        $reads = $response->toolResults->filter(fn (ToolResult $result): bool => $result->name === 'LoadSkill'
            && $result->successful()
            && ($result->arguments['name'] ?? null) === $name);

        if (! str_starts_with($reads->first()?->text() ?? '', "<skill_content name=\"{$name}\">")) {
            throw new RuntimeException("Meal planner did not load the instructions for [{$name}].");
        }

        if ($this->dietType === DietType::Mediterranean) {
            $scoreCard = $reads->firstWhere('arguments.path', self::MEDITERRANEAN_SCORE_CARD)?->text() ?? '';

            if (blank($scoreCard) || str_starts_with($scoreCard, 'File [')) {
                throw new RuntimeException('Meal planner did not load the Mediterranean nutrient score card.');
            }
        }
    }
}
