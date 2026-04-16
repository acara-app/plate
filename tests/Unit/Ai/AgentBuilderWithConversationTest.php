<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Enums\AgentMode;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\User;

covers(AgentBuilder::class);

it('includes summaries in instructions when conversationId is provided', function (): void {
    $builder = resolve(AgentBuilder::class);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
        'sequence_number' => 1,
        'summary' => 'First summary',
        'topics' => ['topic1'],
    ]);
    ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
        'sequence_number' => 2,
        'summary' => 'Second summary',
        'topics' => ['topic2'],
    ]);

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'Hello',
        mode: AgentMode::Ask,
        conversationId: $conversation->id,
    );

    $result = $builder->build($payload, $user);

    expect($result['instructions'])->toContain('First summary')
        ->toContain('Second summary');
});
