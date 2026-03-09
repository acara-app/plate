<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\GetUserProfileContextAction;
use App\Enums\AgentMode;
use App\Models\User;
use App\Services\ToolRegistry;
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

    private ?User $user = null;

    public function __construct(
        private readonly ToolRegistry $toolRegistry,
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

    public function withUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function instructions(): string
    {
        $participant = $this->conversationParticipant();
        $user = $participant instanceof User ? $participant : $this->user;

        if (! $user instanceof User) {
            $profileData = [
                'context' => 'No user context available.',
            ];
        } else {
            $profileData = resolve(GetUserProfileContextAction::class)->handle($user);
        }

        $languageCode = $user instanceof User ? ($user->preferred_language ?? 'en') : 'en';
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
        $tools = $this->toolRegistry->getTools();

        if ($this->attachments !== []) {
            $imageTools = $this->toolRegistry->getImageTools($this->attachments);
            $tools = [...$tools, ...$imageTools];
        }

        if ($this->webSearchEnabled) {
            $providerTools = $this->toolRegistry->getProviderTools();
            $tools = [...$tools, ...$providerTools];
        }

        return [...$tools, ...$this->additionalTools];
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
