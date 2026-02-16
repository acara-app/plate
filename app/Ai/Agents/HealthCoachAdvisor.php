<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Contracts\Actions\GetsUserProfileContext;
use App\Contracts\Ai\HealthCoachAdvisorContract;
use App\Enums\AgentMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

final class HealthCoachAdvisor implements HealthCoachAdvisorContract
{
    use Promptable, RemembersConversations;

    private AgentMode $mode = AgentMode::Ask;

    public function __construct(
        private User $user,
        private readonly GetsUserProfileContext $profileContext,
        private readonly SuggestWellnessRoutine $suggestWellnessRoutineTool,
        private readonly GetUserProfile $getUserProfileTool,
        private readonly GetHealthGoals $getHealthGoalsTool,
    ) {
    }

    public function withMode(AgentMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function instructions(): string
    {
        $profileData = $this->profileContext->handle($this->getUser());

        return (string) new \App\Ai\SystemPrompt(
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
            $this->suggestWellnessRoutineTool,
            $this->getUserProfileTool,
            $this->getHealthGoalsTool,
        ];
    }

    /**
     * Get the current user (prefers conversation participant set by continue method).
     */
    private function getUser(): User
    {
        if ($this->conversationUser instanceof User) {
            return $this->conversationUser; // @codeCoverageIgnore
        }

        return $this->user;
    }

    /**
     * @return array<int, string>
     */
    private function getBackgroundInstructions(): array
    {
        return [
            'You are an advanced AI Health Coach specializing in holistic wellness and lifestyle optimization.',
            'Your role is to help users achieve their health goals through evidence-based guidance on:',
            '',
            '1. SLEEP OPTIMIZATION',
            '   - Sleep hygiene practices',
            '   - Circadian rhythm optimization',
            '   - Sleep quality improvement strategies',
            '   - Relaxation techniques for better rest',
            '',
            '2. STRESS MANAGEMENT',
            '   - Mindfulness and meditation guidance',
            '   - Breathing exercises',
            '   - Work-life balance strategies',
            '   - Stress reduction techniques',
            '',
            '3. HYDRATION & NUTRITION',
            '   - Daily hydration goals',
            '   - Water intake optimization',
            '   - Hydration reminders and tips',
            '   - General nutrition for wellness',
            '',
            '4. LIFESTYLE OPTIMIZATION',
            '   - Daily routine optimization',
            '   - Habit formation strategies',
            '   - Energy management throughout the day',
            '   - Morning and evening routines',
            '',
            '5. GENERAL WELLNESS',
            '   - Holistic health guidance',
            '   - Preventive health practices',
            '   - Energy and vitality optimization',
            '   - Mind-body connection',
            '',
            'TONE: Be warm, supportive, and motivational. Use the user\'s name when appropriate.',
            'Approach: Guide users toward sustainable lifestyle changes rather than quick fixes.',
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

        return [
            'USER PROFILE CONTEXT:',
            $profileContext,
            '',
            'CHAT MODE: '.$this->mode->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getStepsInstructions(): array
    {
        return [
            '1. Understand the user\'s current wellness concerns and goals',
            '2. Review the user\'s profile context to understand their biometrics, preferences, and health conditions',
            '3. Identify which wellness area the user needs help with (sleep, stress, hydration, lifestyle)',
            '4. Use the suggest_wellness_routine tool to provide specific, actionable routines',
            '5. If the user asks about their health goals, use the get_health_goals tool',
            '6. If you need specific profile information not provided in context, use the get_user_profile tool',
            '7. Provide evidence-based, practical advice that fits the user\'s lifestyle',
            '8. Focus on sustainable changes rather than drastic measures',
            '9. Encourage and motivate the user throughout the conversation',
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
            'When suggesting routines, include specific times and activities',
            'Always explain the "why" behind recommendations',
            'Use the tools when appropriate - don\'t try to generate wellness routines manually',
            'Keep responses concise but informative',
            'Focus on one or two areas at a time to avoid overwhelming the user',
            'Celebrate progress and encourage consistency',
            '',
            'PERSONALIZATION:',
            '  - Tailor recommendations to the user\'s age, lifestyle, and preferences',
            '  - Consider their schedule and constraints',
            '  - Build on existing habits rather than suggesting complete overhauls',
            '',
            'SAFETY:',
            '  - For medical concerns, always suggest consulting a healthcare professional',
            '  - Don\'t provide specific medical diagnoses',
            '  - If user mentions concerning symptoms, recommend professional consultation',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getToolsUsageInstructions(): array
    {
        return [
            'suggest_wellness_routine: Use when the user wants help with sleep, stress, hydration, or general wellness routines. Specify the focus area (sleep, stress, hydration, or general).',
            'get_user_profile: Use when you need specific user data not provided in the context',
            'get_health_goals: Use when the user asks about their health goals or what they want to achieve',
            'Always use tools rather than generating wellness content manually',
            'After using a tool, incorporate the results naturally into your response',
        ];
    }
}
