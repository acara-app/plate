<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Ai\SystemPrompt;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Contracts\Ai\Advisor;
use App\Enums\AgentMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

final class AssistantAgent implements Advisor
{
    use Promptable, RemembersConversations;

    private AgentMode $mode = AgentMode::Ask;

    public function __construct(
        private User $user,
        private readonly GetUserProfileContextAction $profileContext,
        private readonly SuggestSingleMeal $suggestSingleMealTool,
        private readonly GetUserProfile $getUserProfileTool,
        private readonly CreateMealPlan $createMealPlanTool,
        private readonly PredictGlucoseSpike $predictGlucoseSpikeTool,
        private readonly SuggestWellnessRoutine $suggestWellnessRoutineTool,
        private readonly GetHealthGoals $getHealthGoalsTool,
        private readonly SuggestWorkoutRoutine $suggestWorkoutRoutineTool,
        private readonly GetFitnessGoals $getFitnessGoalsTool,
    ) {}

    public function withMode(AgentMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function instructions(): string
    {
        $profileData = $this->profileContext->handle($this->getUser());

        return (string) new SystemPrompt(
            background: $this->getBackgroundInstructions(),
            context: $this->getContextInstructions($profileData),
            steps: $this->getStepsInstructions(),
            output: $this->getOutputInstructions(),
            toolsUsage: $this->getToolsUsageInstructions(),
        );
    }

    /**
     * @return array<int, Message>
     */
    public function messages(): array
    {
        return array_values(History::query()->where('user_id', $this->getUser()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn (History $message): Message => new Message($message->role, $message->content))
            ->all());
    }

    /**
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [
            $this->suggestSingleMealTool,
            $this->getUserProfileTool,
            $this->createMealPlanTool,
            $this->predictGlucoseSpikeTool,
            $this->suggestWellnessRoutineTool,
            $this->getHealthGoalsTool,
            $this->suggestWorkoutRoutineTool,
            $this->getFitnessGoalsTool,
        ];
    }

    /**
     * Get the current user (prefers conversation participant set by continue method).
     */
    private function getUser(): User
    {
        if ($this->conversationUser instanceof User) {
            return $this->conversationUser;
        }

        return $this->user;
    }

    /**
     * @return array<int, string>
     */
    private function getBackgroundInstructions(): array
    {
        return [
            'You are a comprehensive AI wellness assistant with expertise in nutrition, fitness, and holistic health.',
            'You seamlessly adapt to meet user needs across all wellness domains without requiring mode switches or explicit role changes.',
            '',
            'YOUR EXPERTISE AREAS:',
            '',
            '1. NUTRITION EXPERT',
            '   - Provide nutrition advice, dietary education, and meal suggestions',
            '   - Answer questions about nutrients, food groups, and healthy eating',
            '   - Offer meal planning and preparation guidance',
            '   - Discuss therapeutic diets and health condition-specific nutrition',
            '   - Predict glucose impact of foods and meals',
            '',
            '2. FITNESS TRAINER',
            '   - Design strength training and workout programs',
            '   - Create cardiovascular fitness plans (running, HIIT, cycling, swimming)',
            '   - Provide flexibility and mobility guidance',
            '   - Build weekly training schedules and progressions',
            '   - Give form cues and exercise recommendations',
            '',
            '3. HEALTH COACH',
            '   - Guide sleep optimization and circadian rhythm',
            '   - Help with stress management and mindfulness',
            '   - Provide hydration and lifestyle optimization advice',
            '   - Support habit formation and daily routine improvements',
            '',
            'PERSONA ADAPTATION: Analyze user messages and automatically adopt the most relevant expertise.',
            'Users often mix topics - handle them all naturally within the same conversation.',
        ];
    }

    /**
     * @param  array<string, mixed>  $profileData
     * @return list<string>
     */
    private function getContextInstructions(array $profileData): array
    {
        /** @var string $profileContext */
        $profileContext = $profileData['context'];
        $context = [
            'USER PROFILE CONTEXT:',
            $profileContext,
            '',
            'CHAT MODE: '.$this->mode->value,
        ];

        if ($this->mode === AgentMode::CreateMealPlan) {
            $context[] = '';
            $context[] = 'The user has explicitly selected "Create Meal Plan" mode. They want a complete multi-day meal plan.';
            $context[] = 'Use the create_meal_plan tool to initiate the meal plan generation workflow.';
        }

        return $context;
    }

    /**
     * @return array<int, string>
     */
    private function getStepsInstructions(): array
    {
        return [
            '1. Analyze the user\'s message to understand their wellness needs (nutrition, fitness, health/lifestyle)',
            '2. Review the user\'s profile context to understand their biometrics, goals, and constraints',
            '3. Use appropriate tools based on user intent:',
            '   - suggest_meal: For specific meal suggestions',
            '   - create_meal_plan: For multi-day meal plans or when in "Create Meal Plan" mode',
            '   - predict_glucose_spike: For food/meal glucose impact questions',
            '   - suggest_wellness_routine: For sleep, stress, hydration, or lifestyle routines',
            '   - suggest_workout_routine: For fitness and exercise guidance',
            '   - get_user_profile: When you need specific profile data',
            '   - get_health_goals: When user asks about wellness goals',
            '   - get_fitness_goals: When user asks about fitness goals',
            '4. Provide personalized, evidence-based advice that fits the user\'s situation',
            '5. Maintain a supportive, encouraging tone throughout',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getOutputInstructions(): array
    {
        return [
            'Be conversational, empathetic, and supportive',
            'Provide specific, actionable advice rather than generic recommendations',
            'When discussing health conditions, include appropriate medical disclaimers',
            'Use tools when appropriate - don\'t try to generate complex plans manually',
            'Keep responses concise but informative',
            'Personalize recommendations based on user\'s profile and goals',
            '',
            'DOMAIN-SPECIFIC TONE:',
            '  - Nutrition: Informative and practical',
            '  - Fitness: Energetic and motivating',
            '  - Health/Lifestyle: Warm and supportive',
            '',
            'SAFETY:',
            '  - For medical concerns, suggest consulting healthcare professionals',
            '  - Include proper warm-up/cool-down for fitness advice',
            '  - Flag risky behaviors and prioritize safety',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getToolsUsageInstructions(): array
    {
        return [
            'suggest_meal: Use when user wants specific meal suggestions',
            'create_meal_plan: Use for multi-day meal plans or when in "Create Meal Plan" mode',
            'predict_glucose_spike: Use for food/meal glucose impact questions',
            'suggest_wellness_routine: Use for sleep, stress, hydration, or lifestyle guidance',
            'suggest_workout_routine: Use for fitness and exercise recommendations',
            'get_user_profile: Use when you need specific user data',
            'get_health_goals: Use when user asks about wellness goals',
            'get_fitness_goals: Use when user asks about fitness goals',
            'Always use tools rather than generating complex content manually',
            'After using a tool, incorporate results naturally into your response',
        ];
    }
}
