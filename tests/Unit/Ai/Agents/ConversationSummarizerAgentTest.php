<?php

declare(strict_types=1);

use App\Ai\Agents\ConversationSummarizerAgent;
use App\Contracts\SummarizesConversation;

it('implements SummarizesConversation contract', function (): void {
    $agent = new ConversationSummarizerAgent();

    expect($agent)->toBeInstanceOf(SummarizesConversation::class);
});

it('returns instructions from view', function (): void {
    $agent = new ConversationSummarizerAgent();

    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
});
