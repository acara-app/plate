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
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Skills\Skill;

#[MaxTokens(64000)]
#[Timeout(180)]
final class MealPlanAgent implements Agent, BillsUsageToUser, GeneratesMealPlans, HasProviderOptions, HasStructuredOutput
{
    use HasUsageUser;
    use Promptable;
    use ReasonsWithMediumEffort;

    private DietType $dietType = DietType::Balanced;

    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly AnalyzeGlucoseForNotificationAction $analyzeGlucose,
    ) {}

    public function withDietType(DietType $dietType): self
    {
        $this->dietType = $dietType;

        return $this;
    }

    public function instructions(): string
    {
        $directory = resource_path('skills/diets/'.$this->dietType->skillName());

        return implode("\n\n", [
            <<<'PROMPT'
                Create one day of meals for the selected diet by following the guide below. Respect the user's allergies, medical restrictions and preferences.
                Return the meals using the requested schema, with ingredient quantities, portions and estimated nutrition values.
                PROMPT,
            Skill::fromDirectory($directory)?->instructions,
            ...array_map(File::get(...), glob($directory.'/references/*.md') ?: []),
        ]);
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

        return DayMealsData::from($response->toArray());
    }
}
