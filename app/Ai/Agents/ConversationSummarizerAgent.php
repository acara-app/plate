<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Contracts\SummarizesConversation;
use App\Models\ConversationSummary;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('openai')]
#[MaxTokens(4000)]
#[Timeout(90)]
final class ConversationSummarizerAgent implements Agent, SummarizesConversation
{
    use Promptable;

    private ?ConversationSummary $previousSummary = null;

    public function instructions(): string
    {
        return view('ai.prompts.conversation-summarizer', [
            'previousSummary' => $this->previousSummary,
        ])->render();
    }

    // @codeCoverageIgnoreStart
    public function summarize(string $conversationText, ?ConversationSummary $previousSummary): string
    {
        $this->previousSummary = $previousSummary;

        return (string) $this->prompt(
            prompt: $conversationText,
            model: 'gpt-5-nano',
        );
    }

    // @codeCoverageIgnoreEnd
}
