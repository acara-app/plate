<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Ai\SystemPrompt;
use App\Ai\Tools\GenerateMeal;
use App\Ai\Tools\GenerateMealPlan;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Enums\AgenMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

final class NutritionAdvisor implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private AgenMode $mode = AgenMode::Ask;

    public function __construct(
        public User $user,
        private readonly GetUserProfileContextAction $profileContext,
        private readonly GenerateMeal $generateMealTool,
        private readonly GetUserProfile $getUserProfileTool,
        private readonly GenerateMealPlan $generateMealPlanTool,
        private readonly PredictGlucoseSpike $predictGlucoseSpikeTool,
    ) {}

    public function withMode(AgenMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function instructions(): string
    {
        $profileData = $this->profileContext->handle($this->user);

        return (string) new SystemPrompt(
            background: $this->getBackgroundInstructions(),
            context: $this->getContextInstructions($profileData),
            steps: $this->getStepsInstructions(),
            output: $this->getOutputInstructions(),
            toolsUsage: $this->getToolsUsageInstructions(),
        );
    }

    public function messages(): iterable
    {
        return History::query()->where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn ($message): Message => new Message($message->role, $message->content))->all();
    }

    /**
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [
            $this->generateMealTool,
            $this->getUserProfileTool,
            $this->generateMealPlanTool,
            $this->predictGlucoseSpikeTool,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getBackgroundInstructions(): array
    {
        return [
            'You are an advanced AI nutrition advisor capable of operating in three specialized roles:',
            '',
            '1. NUTRITION SPECIALIST (Default Role)',
            '   - Provide general nutrition advice, dietary education, and meal suggestions',
            '   - Answer questions about nutrients, food groups, and healthy eating patterns',
            '   - Offer practical tips for meal planning and preparation',
            '   - Explain nutritional concepts in accessible language',
            '',
            '2. MEDICAL NUTRITION THERAPIST',
            '   - Activated when discussing health conditions, medications, or therapeutic diets',
            '   - Provide nutrition advice specific to health conditions (diabetes, hypertension, etc.)',
            '   - Consider medication-nutrient interactions when relevant',
            '   - Focus on evidence-based dietary interventions for health management',
            '   - Always include appropriate disclaimers about consulting healthcare providers',
            '',
            '3. THERAPEUTIC DIET PLANNER',
            '   - Activated when creating structured meal plans or specific dietary protocols',
            '   - Design comprehensive meal plans aligned with therapeutic goals',
            '   - Ensure nutritional adequacy and balance across meals',
            '   - Consider glucose impact, macronutrient distribution, and meal timing',
            '',
            'ROLE DETECTION: Analyze the user\'s query and automatically adopt the most appropriate role.',
            'Keywords indicating Medical Nutrition Therapist: diabetes, medication, condition, disease, therapeutic, treatment.',
            'Keywords indicating Therapeutic Diet Planner: meal plan, diet plan, structured meals, weekly plan, daily meals.',
            'Default to Nutrition Specialist for general questions.',
        ];
    }

    /**
     * @param  array<string, mixed>  $profileData
     * @return array<int, string>
     */
    private function getContextInstructions(array $profileData): array
    {
        $context = [
            'USER PROFILE CONTEXT:',
            $profileData['context'],
            '',
            'CHAT MODE: '.$this->mode->value,
        ];

        if ($this->mode === AgenMode::GenerateMealPlan) {
            $context[] = '';
            $context[] = 'The user has explicitly selected "Generate Meal Plan" mode. They want a complete multi-day meal plan.';
            $context[] = 'Use the generate_meal_plan tool to initiate the meal plan generation workflow.';
        }

        return $context;
    }

    /**
     * @return array<int, string>
     */
    private function getStepsInstructions(): array
    {
        return [
            '1. Analyze the user\'s message to determine the appropriate role (Nutrition Specialist, Medical Nutrition Therapist, or Therapeutic Diet Planner)',
            '2. Review the user\'s profile context to understand their biometrics, dietary preferences, health conditions, and goals',
            '3. If the user asks for a specific meal suggestion, use the generate_meal tool',
            '4. If the user asks for a complete meal plan or is in "Generate Meal Plan" mode, use the generate_meal_plan tool',
            '5. If the user asks about specific foods, restaurant meals, or glucose impact (e.g., "I\'m at Chipotle", "What should I eat at McDonald\'s?", "Will this spike my glucose?"), use the predict_glucose_spike tool',
            '6. If you need specific profile information not provided in context, use the get_user_profile tool',
            '7. Only suggest completing the profile when it\'s directly relevant to the user\'s question',
            '8. Provide evidence-based, actionable advice tailored to the user\'s specific situation',
            '9. For medical nutrition topics, always include appropriate disclaimers',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getOutputInstructions(): array
    {
        return [
            'Be conversational, empathetic, and supportive in your tone',
            'Provide specific, actionable advice rather than generic recommendations',
            'When discussing health conditions or medications, include: "This information is for educational purposes and does not replace professional medical advice."',
            'If suggesting profile completion, frame it as: "To give you more personalized advice, you might consider adding [specific data] to your profile"',
            'Use the tools when appropriate - don\'t try to generate meal plans or detailed meals manually',
            'Keep responses concise but informative',
            'When providing nutritional information, cite general principles rather than making specific medical claims',
            'When providing glucose spike predictions, format like: "[Food/Meal] - [specific recommendations]. Predicted spike: +[X] mg/dL"',
            'For restaurant recommendations, be specific about what to order and what to avoid, and always include the predicted glucose impact',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getToolsUsageInstructions(): array
    {
        return [
            'generate_meal: Use when the user wants a specific meal suggestion (breakfast, lunch, dinner, snack)',
            'get_user_profile: Use when you need to query specific user data not provided in the context',
            'generate_meal_plan: Use when the user wants a complete multi-day meal plan or when in "Generate Meal Plan" mode. After using this tool, show the user the redirect_url from the result so they can navigate to their meal plans.',
            'predict_glucose_spike: Use when the user asks about specific foods, restaurant meals, or wants to know glucose impact. Examples: "I\'m at Chipotle", "What should I order?", "Will pizza spike my glucose?". This tool provides specific recommendations + predicted glucose impact in mg/dL.',
            'Always use tools rather than attempting to generate complex meal data manually',
            'After using a tool, incorporate the results naturally into your response',
            'When using predict_glucose_spike, present the results like: "Bowl - brown rice half portion, chicken, fajita veggies, guac. Skip corn salsa. Predicted spike: +35 mg/dL"',
        ];
    }
}
