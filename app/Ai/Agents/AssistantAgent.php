<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Ai\SystemPrompt;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthEntries;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Ai\Tools\UpdateUserProfileAttributes;
use App\Enums\AgentMode;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Providers\Tools\WebSearch;

#[Timeout(120)]
final class AssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private AgentMode $mode = AgentMode::Ask;

    /**
     * @var array<int, Base64Image>
     */
    private array $attachments = [];

    private bool $webSearchEnabled = false;

    /**
     * @var array<int, Tool|ProviderTool>
     */
    private array $additionalTools = [];

    public function __construct(
        private User $user,
        private readonly GetUserProfileContextAction $profileContext,
    ) {}

    public function addTool(Tool|ProviderTool $tool): self
    {
        $this->additionalTools[] = $tool;

        return $this;
    }

    /**
     * @param  array<int, Base64Image>  $attachments
     */
    public function withAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function withWebSearch(): self
    {
        $this->webSearchEnabled = true;

        return $this;
    }

    public function withMode(AgentMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function instructions(): string
    {
        $participant = $this->conversationParticipant();
        $user = $participant instanceof User ? $participant : $this->user;
        $profileData = $this->profileContext->handle($user);

        return (string) new SystemPrompt(
            background: $this->getBackgroundInstructions(),
            context: $this->getContextInstructions($profileData, $user),
            steps: $this->getStepsInstructions(),
            output: $this->getOutputInstructions(),
            toolsUsage: $this->getToolsUsageInstructions(),
        );
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function tools(): array
    {
        $tools = [
            new SuggestSingleMeal,
            new GetUserProfile,
            new CreateMealPlan,
            new PredictGlucoseSpike,
            new SuggestWellnessRoutine,
            new GetHealthGoals,
            new GetHealthEntries,
            new LogHealthEntry,
            new SuggestWorkoutRoutine,
            new GetFitnessGoals,
            new GetDietReference,
            new EnrichAttributeMetadata,
            new UpdateUserProfileAttributes,
        ];

        if ($this->attachments !== []) {
            $tools[] = new AnalyzePhoto($this->attachments);
        }

        if ($this->webSearchEnabled) {
            $tools[] = new WebSearch;
        }

        return array_merge($tools, $this->additionalTools);
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
            '4. IMAGE ANALYSIS',
            '   - You can see and analyze images that users share',
            '   - When a user shares a food photo, use the analyze_photo tool for detailed nutritional breakdown',
            '   - After receiving photo analysis results, present a clear summary of the detected food items and nutritional data to the user and ask for confirmation before logging with log_health_entry',
            '   - For non-food images, respond using your built-in vision capabilities',
            '',
            'PERSONA ADAPTATION: Analyze user messages and automatically adopt the most relevant expertise.',
            'Users often mix topics - handle them all naturally within the same conversation.',
        ];
    }

    /**
     * @param  array<string, mixed>  $profileData
     * @return list<string>
     */
    private function getContextInstructions(array $profileData, User $user): array
    {
        /** @var string $profileContext */
        $profileContext = $profileData['context'];
        $languageCode = $user->preferred_language ?? 'en';
        $languageLabel = LanguageUtil::get($languageCode) ?? 'English';

        $timezone = $this->resolveTimezone();

        $context = [
            'USER PROFILE CONTEXT:',
            $profileContext,
            '',
            'CURRENT TIME: '.now($timezone)->format('Y-m-d H:i (l)').' ('.$timezone.')',
            '',
            'CHAT MODE: '.$this->mode->value,
            '',
            'LANGUAGE: Your default language is '.$languageLabel.' ('.$languageCode.'). Respond in this language unless the user writes in a different language — in that case, naturally mirror their language.',
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
            "1. Analyze the user's message to understand their wellness needs (nutrition, fitness, health/lifestyle)",
            "2. Review the user's profile context to understand their biometrics, goals, and constraints",
            '3. Use appropriate tools based on user intent:',
            '   - analyze_photo: When user shares a food photo, use this for detailed nutritional analysis. Then present the results to the user for review before logging with log_health_entry',
            '   - log_health_entry: When user reports food, glucose, weight, BP, insulin, meds, or exercise, extract the data and present a summary for confirmation. Log only after the user confirms or adjusts values.',
            '   - suggest_meal: For specific meal suggestions',
            '   - create_meal_plan: For multi-day meal plans or when in "Create Meal Plan" mode',
            '   - predict_glucose_spike: For food/meal glucose impact questions',
            '   - suggest_wellness_routine: For sleep, stress, hydration, or lifestyle routines',
            '   - suggest_workout_routine: For fitness and exercise guidance',
            '   - get_user_profile: When you need specific profile data',
            "   - get_health_entries: For retrieving user's logged health data (food log, glucose readings, vitals, exercise)",
            '   - get_health_goals: When user asks about wellness goals',
            '   - get_fitness_goals: When user asks about fitness goals',
            '   - enrich_attribute_metadata: When user mentions a new health condition, allergy, restriction, or dietary pattern, call this FIRST to generate dietary metadata before saving',
            '   - update_user_profile_attributes: After enriching metadata, use this to add/update/remove profile attributes (allergies, health conditions, medications, dietary patterns)',
            "4. Provide personalized, evidence-based advice that fits the user's situation",
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
            "Use tools when appropriate - don't try to generate complex plans manually",
            'Keep responses concise but informative',
            "Personalize recommendations based on user's profile and goals",
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
            'analyze_photo: Use when the user shares a food photo or image of a meal. This tool performs detailed nutritional analysis of the food in the image and returns structured data including calories, protein, carbs, fat, and portion sizes. After receiving the results, present a clear summary to the user (food items, calories, carbs, protein, fat) and ask for confirmation before logging with log_health_entry. The user may adjust values before confirming.',
            'log_health_entry: Use when user reports eating food, glucose readings, weight, blood pressure, insulin, medications, or exercise. Extract what you can and estimate values if needed. Log all macros when user provides them: carbs, protein, fat, and calories. Before calling this tool, present the extracted data to the user and ask them to confirm. If the user provides corrections, apply them before logging. Do NOT call this tool without user confirmation.',
            'suggest_meal: Use when user wants specific meal suggestions',
            'create_meal_plan: Use for multi-day meal plans or when in "Create Meal Plan" mode',
            'predict_glucose_spike: Use for food/meal glucose impact questions',
            'suggest_wellness_routine: Use for sleep, stress, hydration, or lifestyle guidance',
            'suggest_workout_routine: Use for fitness and exercise recommendations',
            'get_user_profile: Use when you need specific user data',
            'get_health_entries: Use when user asks about their logged data, food log, health history, what they ate, or wants to compare actual intake vs meal plan',
            'get_health_goals: Use when user asks about wellness goals',
            'get_fitness_goals: Use when user asks about fitness goals',
            'enrich_attribute_metadata: Use when the user mentions a new health condition, allergy, dietary restriction, or dietary pattern. Call this tool FIRST to generate structured dietary metadata (safety levels, foods to avoid, dietary rules). Then pass the resulting metadata to update_user_profile_attributes to save the attribute.',
            'update_user_profile_attributes: Use to add, update, remove, or list user profile attributes (allergies, intolerances, dietary patterns, dislikes, restrictions, health conditions, medications). When adding a new attribute, first call enrich_attribute_metadata to get structured metadata, then call this tool with the metadata included. For medications, include dosage, frequency, and purpose in the metadata field.',
            'Always use tools rather than generating complex content manually',
            'After using a tool, incorporate results naturally into your response',
        ];
    }

    private function resolveTimezone(): string
    {
        /** @var string|null $sessionTimezone */
        $sessionTimezone = session('timezone');

        return $sessionTimezone
            ?? $this->user->timezone
            ?? 'UTC';
    }
}
