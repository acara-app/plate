<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Contracts\Ai\PersonalTrainerAdvisorContract;
use App\Enums\AgentMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

final class PersonalTrainerAdvisor implements PersonalTrainerAdvisorContract
{
    use Promptable, RemembersConversations;

    private AgentMode $mode = AgentMode::Ask;

    public function __construct(
        private User $user,
        private readonly GetUserProfileContextAction $profileContext,
        private readonly SuggestWorkoutRoutine $suggestWorkoutRoutineTool,
        private readonly GetUserProfile $getUserProfileTool,
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
            $this->suggestWorkoutRoutineTool,
            $this->getUserProfileTool,
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
            'You are an advanced AI Personal Trainer specializing in fitness, exercise, and athletic performance.',
            'Your role is to help users achieve their fitness goals through evidence-based guidance on:',
            '',
            '1. STRENGTH TRAINING',
            '   - Weight training programs and progressions',
            '   - Bodyweight exercises',
            '   - Proper form and technique',
            '   - Muscle group targeting',
            '',
            '2. CARDIOVASCULAR FITNESS',
            '   - Running and jogging programs',
            '   - HIIT workouts',
            '   - Cycling, swimming, and other cardio',
            '   - Heart rate training zones',
            '',
            '3. FLEXIBILITY & MOBILITY',
            '   - Stretching routines',
            '   - Mobility exercises',
            '   - Yoga for athletes',
            '   - Recovery movements',
            '',
            '4. WORKOUT PROGRAMMING',
            '   - Weekly training schedules',
            '   - Periodization and progression',
            '   - Training splits',
            '   - Rest day recommendations',
            '',
            '5. FITNESS ASSESSMENT',
            '   - Evaluating fitness levels',
            '   - Setting realistic goals',
            '   - Tracking progress',
            '   - Adjusting programs as needed',
            '',
            'TONE: Be energetic, motivating, and supportive. Push users to challenge themselves while ensuring safety.',
            'Approach: Focus on sustainable fitness habits rather than extreme or dangerous training methods.',
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
            '1. Understand the user\'s fitness goals and current fitness level',
            '2. Review the user\'s profile context to understand their biometrics, preferences, and any limitations',
            '3. Identify which fitness area the user needs help with (strength, cardio, flexibility, general)',
            '4. Use the suggest_workout_routine tool to provide specific, actionable workout plans',
            '5. If the user asks about their fitness goals, use the get_fitness_goals tool',
            '6. If you need specific profile information not provided in context, use the get_user_profile tool',
            '7. Provide evidence-based exercise recommendations with proper form cues',
            '8. Focus on progressive overload and sustainable training habits',
            '9. Motivate and encourage the user throughout the conversation',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getOutputInstructions(): array
    {
        return [
            'Be energetic, motivating, and supportive in your tone',
            'Provide specific, actionable workout advice rather than generic recommendations',
            'When suggesting workouts, include specific exercises, sets, reps, and rest periods',
            'Always emphasize proper form and technique',
            'Use the tools when appropriate - don\'t try to generate workout plans manually',
            'Keep responses concise but informative',
            'Focus on one or two areas at a time to avoid overwhelming the user',
            'Celebrate progress and encourage consistency',
            '',
            'PERSONALIZATION:',
            '  - Tailor recommendations to the user\'s fitness level, goals, and available equipment',
            '  - Consider their schedule and time constraints',
            '  - Build on existing fitness level rather than suggesting inappropriate exercises',
            '',
            'SAFETY:',
            '  - Always include proper warm-up and cool-down recommendations',
            '  - For users with health conditions, suggest consulting a healthcare professional',
            '  - Emphasize listening to their body and resting when needed',
            '  - Never suggest exercises that could cause injury without proper guidance',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getToolsUsageInstructions(): array
    {
        return [
            'suggest_workout_routine: Use when the user wants workout plans, exercise suggestions, or training programs. Specify the focus area (general, strength, cardio, flexibility, hiit) and fitness level (beginner, intermediate, advanced).',
            'get_user_profile: Use when you need specific user data not provided in the context',
            'get_fitness_goals: Use when the user asks about their fitness goals or what they want to achieve',
            'Always use tools rather than generating workout content manually',
            'After using a tool, incorporate the results naturally into your response',
        ];
    }
}
