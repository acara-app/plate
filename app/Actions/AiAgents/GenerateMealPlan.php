<?php

declare(strict_types=1);

namespace App\Actions\AiAgents;

use App\DataObjects\MealPlanData;
use App\Enums\AiModel;
use App\Jobs\ProcessMealPlanJob;
use App\Models\JobTracking;
use App\Models\User;
use App\Traits\Trackable;
use Illuminate\Contracts\Bus\Dispatcher;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final class GenerateMealPlan
{
    use Trackable;

    public function __construct(
        private readonly CreateMealPlanPrompt $createPrompt,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function handle(User $user, AiModel $model = AiModel::Gemini25Flash): JobTracking
    {
        $job = new ProcessMealPlanJob($user->id, $model);
        $this->dispatcher->dispatch($job);

        return $this->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);
    }

    public function generate(User $user, AiModel $model): MealPlanData
    {
        $prompt = $this->createPrompt->handle($user);

        $schema = $this->buildSchema();

        $response = Prism::structured()
            ->using(Provider::Gemini, $model->value)
            ->withSystemPrompt('You are a professional nutritionist and dietitian specializing in creating personalized meal plans.')
            ->withPrompt($prompt)
            ->withSchema($schema)
            ->withMaxTokens(70000)
            ->withClientOptions([
                'timeout' => 180,
            ])
            ->asStructured();

        /** @var array<string, mixed> $structuredData */
        $structuredData = $response->structured;

        return MealPlanData::from($structuredData);
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'meal_plan',
            description: 'A comprehensive personalized meal plan with detailed meals',
            properties: [
                new EnumSchema(
                    name: 'type',
                    description: 'The type of meal plan',
                    options: ['weekly', 'monthly', 'custom']
                ),
                new StringSchema(
                    name: 'name',
                    description: 'A descriptive name for the meal plan'
                ),
                new StringSchema(
                    name: 'description',
                    description: 'A brief description of the meal plan and its goals'
                ),
                new NumberSchema(
                    name: 'duration_days',
                    description: 'The total number of days in the meal plan'
                ),
                new NumberSchema(
                    name: 'target_daily_calories',
                    description: 'The target daily calorie intake'
                ),
                new ObjectSchema(
                    name: 'macronutrient_ratios',
                    description: 'The target macronutrient distribution',
                    properties: [
                        new NumberSchema('protein', 'Protein percentage'),
                        new NumberSchema('carbs', 'Carbohydrate percentage'),
                        new NumberSchema('fat', 'Fat percentage'),
                    ],
                    requiredFields: ['protein', 'carbs', 'fat']
                ),
                new ArraySchema(
                    name: 'meals',
                    description: 'Array of all meals in the plan',
                    items: new ObjectSchema(
                        name: 'meal',
                        description: 'A single meal with detailed nutritional information',
                        properties: [
                            new NumberSchema(
                                name: 'day_number',
                                description: 'The day number in the plan (1-based)'
                            ),
                            new EnumSchema(
                                name: 'type',
                                description: 'The meal type',
                                options: ['breakfast', 'lunch', 'dinner', 'snack']
                            ),
                            new StringSchema(
                                name: 'name',
                                description: 'The name of the meal'
                            ),
                            new StringSchema(
                                name: 'description',
                                description: 'A brief description of the meal'
                            ),
                            new StringSchema(
                                name: 'preparation_instructions',
                                description: 'Step-by-step instructions for preparing the meal'
                            ),
                            new ArraySchema(
                                name: 'ingredients',
                                description: 'Array of ingredients with their quantities',
                                items: new ObjectSchema(
                                    name: 'ingredient',
                                    description: 'A single ingredient with quantity',
                                    properties: [
                                        new StringSchema(
                                            name: 'name',
                                            description: 'The ingredient name (e.g., "Chicken breast", "Brown rice")'
                                        ),
                                        new StringSchema(
                                            name: 'quantity',
                                            description: 'The quantity with unit (e.g., "150g", "1 cup (185g)", "1 tablespoon (15ml)")'
                                        ),
                                    ],
                                    requiredFields: ['name', 'quantity']
                                )
                            ),
                            new StringSchema(
                                name: 'portion_size',
                                description: 'The recommended portion size'
                            ),
                            new NumberSchema(
                                name: 'calories',
                                description: 'Total calories for this meal'
                            ),
                            new NumberSchema(
                                name: 'protein_grams',
                                description: 'Protein content in grams'
                            ),
                            new NumberSchema(
                                name: 'carbs_grams',
                                description: 'Carbohydrate content in grams'
                            ),
                            new NumberSchema(
                                name: 'fat_grams',
                                description: 'Fat content in grams'
                            ),
                            new NumberSchema(
                                name: 'preparation_time_minutes',
                                description: 'Estimated preparation time in minutes'
                            ),
                            new NumberSchema(
                                name: 'sort_order',
                                description: 'The order of this meal within the day (1 for first meal, 2 for second, etc.)'
                            ),
                        ],
                        requiredFields: [
                            'day_number',
                            'type',
                            'name',
                            'description',
                            'preparation_instructions',
                            'ingredients',
                            'portion_size',
                            'calories',
                            'protein_grams',
                            'carbs_grams',
                            'fat_grams',
                            'preparation_time_minutes',
                            'sort_order',
                        ]
                    )
                ),
                new ObjectSchema(
                    name: 'metadata',
                    description: 'Supplemental information aggregated across the plan',
                    properties: [
                        new StringSchema(
                            name: 'preparation_notes',
                            description: 'High-level batch cooking, storage, or substitution guidance'
                        ),
                    ]
                ),
            ],
            requiredFields: [
                'type',
                'name',
                'description',
                'duration_days',
                'target_daily_calories',
                'macronutrient_ratios',
                'meals',
            ]
        );
    }
}
