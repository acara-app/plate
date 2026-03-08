<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
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

        $languageCode = $user->preferred_language ?? 'en';
        $timezone = $this->resolveTimezone();

        return view('ai.prompts.altani-static', [
            'profileContext' => $profileData['context'],
            'currentTime' => now($timezone)->format('Y-m-d H:i (l)').' ('.$timezone.')',
            'chatMode' => $this->mode->value,
            'languageLabel' => LanguageUtil::get($languageCode) ?? 'English',
            'languageCode' => $languageCode,
            'isCreateMealPlanMode' => $this->mode === AgentMode::CreateMealPlan,
        ])->render();
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
            new GetCalorieLevelGuideline,
            new GetDailyServingsByCalorie,
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

    private function resolveTimezone(): string
    {
        /** @var string|null $sessionTimezone */
        $sessionTimezone = session('timezone');

        return $sessionTimezone
            ?? $this->user->timezone
            ?? 'UTC';
    }
}
