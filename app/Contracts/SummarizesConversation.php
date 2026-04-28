<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Ai\Agents\ConversationSummarizerAgent;
use App\Models\ConversationSummary;
use Illuminate\Container\Attributes\Bind;

#[Bind(ConversationSummarizerAgent::class)]
interface SummarizesConversation
{
    /**
     * @return array{summary: string, topics: array<int, string>, key_facts: array<int, string>, unresolved_threads: array<int, string>, resolved_threads: array<int, string>}
     */
    public function summarize(string $conversationText, ?ConversationSummary $previousSummary): array;
}
