<?php

declare(strict_types=1);

namespace App\Ai;

use App\Actions\GetUserProfileContextAction;
use App\Contracts\Memory\ManagesMemoryContext;
use App\Enums\DataSensitivity;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\ToolSensitivityReader;
use App\Services\Memory\NullMemoryPromptContext;
use App\Services\ToolRegistry;
use App\Utilities\EmergencyNumberUtil;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Providers\Tools\ProviderTool;

final readonly class AgentBuilder
{
    public function __construct(
        private ToolRegistry $toolRegistry,
        private ManagesMemoryContext $memoryContext,
        private ToolSensitivityReader $toolSensitivity,
    ) {}

    /**
     * @return array{instructions: string, tools: array<int, Tool|ProviderTool>}
     */
    public function build(AgentPayload $payload, ?User $user = null): array
    {
        return [
            'instructions' => $this->buildInstructions($payload, $user),
            'tools' => $this->buildTools($payload),
        ];
    }

    public function buildInstructions(AgentPayload $payload, ?User $user): string
    {
        $profileData = $this->getProfileData($user);
        $languageCode = $user instanceof User ? ($user->locale ?? 'en') : 'en';
        $timezone = $this->resolveTimezone($user);

        $summaries = $payload->conversationId !== null
            ? ConversationSummary::getRecentForContext($payload->conversationId)
            : collect();

        $instructions = view('ai.prompts.altani-static', [
            'profileContext' => $profileData['context'],
            'currentTime' => now($timezone)->format('Y-m-d H:i (l)').' ('.$timezone.')',
            'chatMode' => $payload->mode->value,
            'languageLabel' => LanguageUtil::get($languageCode) ?? 'English',
            'languageCode' => $languageCode,
            'isCreateMealPlanMode' => $payload->mode->value === 'create-meal-plan',
            'memoryStorageEnabled' => ! $this->memoryContext instanceof NullMemoryPromptContext,
            'summaries' => $summaries,
            'emergencyNumber' => EmergencyNumberUtil::emergencyNumber($timezone),
        ])->render();

        $memories = $this->renderMemories($payload, $user);

        return $memories === '' ? $instructions : $instructions.PHP_EOL.PHP_EOL.$memories;
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function buildTools(AgentPayload $payload): array
    {
        $tools = $this->toolRegistry->getTools();

        if ($payload->images !== []) {
            $imageTools = $this->toolRegistry->getImageTools($payload->images);
            $tools = [...$tools, ...$imageTools];
        }

        if (
            $payload->shouldEnableWebSearch()
            && $this->toolSensitivity->maxSensitivity($tools) === DataSensitivity::General
        ) {
            $providerTools = $this->toolRegistry->getProviderTools();
            $tools = [...$tools, ...$providerTools];
        }

        return $tools;
    }

    private function renderMemories(AgentPayload $payload, ?User $user): string
    {
        if (! $user instanceof User) {
            return '';
        }

        return $this->memoryContext->render(
            $user->id,
            $payload->message,
            $this->conversationTail($payload->conversationId),
        );
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function conversationTail(?string $conversationId): array
    {
        if ($conversationId === null) {
            return [];
        }

        /** @phpstan-ignore cast.int */
        $limit = (int) config('memory.retrieval.context_turns', 20);

        return History::query()
            ->where('conversation_id', $conversationId)
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
            ->latest('created_at')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(static fn (History $history): array => [
                'role' => $history->role->value,
                'content' => $history->content,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getProfileData(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'context' => 'No user context available.',
            ];
        }

        return resolve(GetUserProfileContextAction::class)->handle($user);
    }

    private function resolveTimezone(?User $user): string
    {
        /** @var string|null $sessionTimezone */
        $sessionTimezone = session('timezone');

        if (! $user instanceof User) {
            return $sessionTimezone ?? 'UTC';
        }

        return $sessionTimezone
            ?? $user->timezone
            ?? 'UTC';
    }
}
