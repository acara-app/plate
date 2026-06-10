<?php

declare(strict_types=1);

namespace App\Ai;

use App\Contracts\Memory\ManagesMemoryContext;
use App\Contracts\Skills\LoadsSkills;
use App\Enums\DataSensitivity;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\ToolSensitivityReader;
use App\Services\Memory\NullMemoryPromptContext;
use App\Services\ToolRegistry;
use App\Utilities\EmergencyNumberUtil;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Providers\Tools\ProviderTool;

final readonly class AgentBuilder
{
    public function __construct(
        private ToolRegistry $toolRegistry,
        private ManagesMemoryContext $memoryContext,
        private ToolSensitivityReader $toolSensitivity,
        private LoadsSkills $skillLoader,
    ) {}

    /**
     * @return array{instructions: string, tools: array<int, Tool|ProviderTool|Agent>}
     */
    public function build(AgentRequest $request, ?User $user = null): array
    {
        return [
            'instructions' => $this->buildInstructions($request, $user),
            'tools' => $this->buildTools($request),
        ];
    }

    public function buildInstructions(AgentRequest $request, ?User $user): string
    {
        $languageCode = $user instanceof User ? ($user->locale ?? 'en') : 'en';
        $timezone = $this->resolveTimezone($user);

        $summaries = $request->hasExistingConversation()
            ? ConversationSummary::getRecentForContext($request->conversationId)
            : collect();

        $instructions = view('ai.prompts.altani-static', [
            'currentTime' => now($timezone)->format('Y-m-d H:i (l)').' ('.$timezone.')',
            'languageLabel' => LanguageUtil::get($languageCode) ?? 'English',
            'languageCode' => $languageCode,
            'memoryStorageEnabled' => ! $this->memoryContext instanceof NullMemoryPromptContext,
            'summaries' => $summaries,
            'emergencyNumber' => EmergencyNumberUtil::emergencyNumber($timezone),
            'availableSkills' => $this->skillLoader->loadAll(),
        ])->render();

        $memories = $this->renderMemories($request, $user);

        return $memories === '' ? $instructions : $instructions.PHP_EOL.PHP_EOL.$memories;
    }

    /**
     * @return array<int, Tool|ProviderTool|Agent>
     */
    public function buildTools(AgentRequest $request): array
    {
        $tools = $this->toolRegistry->getTools();

        if ($request->hasImages()) {
            $imageTools = $this->toolRegistry->getImageTools($request->images);
            $tools = [...$tools, ...$imageTools];
        }

        $subAgents = $this->toolRegistry->getSubAgents();

        if (
            $request->shouldEnableWebSearch()
            && $this->maxReachableSensitivity($tools, $subAgents) === DataSensitivity::General
        ) {
            $providerTools = $this->toolRegistry->getProviderTools();
            $tools = [...$tools, ...$providerTools];
        }

        return [...$tools, ...$subAgents];
    }

    /**
     * @param  array<int, Tool|ProviderTool>  $tools
     * @param  array<int, Agent>  $subAgents
     */
    private function maxReachableSensitivity(array $tools, array $subAgents): DataSensitivity
    {
        foreach ($subAgents as $subAgent) {
            if ($subAgent instanceof HasTools) {
                foreach ($subAgent->tools() as $tool) {
                    $tools[] = $tool;
                }
            }
        }

        return $this->toolSensitivity->maxSensitivity($tools);
    }

    private function renderMemories(AgentRequest $request, ?User $user): string
    {
        if (! $user instanceof User) {
            return '';
        }

        return $this->memoryContext->render(
            $user->id,
            $request->message,
            $this->conversationTail($request->conversationId),
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

        $limit = config()->integer('memory.retrieval.context_turns', 20);

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
