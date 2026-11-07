<?php

declare(strict_types=1);

use App\Ai\Agents\ConversationSummarizerAgent;
use App\Contracts\SummarizesConversation;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

covers(ConversationSummarizerAgent::class);

it('implements SummarizesConversation contract', function (): void {
    $agent = new ConversationSummarizerAgent();

    expect($agent)->toBeInstanceOf(SummarizesConversation::class);
});

it('returns instructions from view', function (): void {
    $agent = new ConversationSummarizerAgent();

    $instructions = $agent->instructions();

    expect($instructions)
        ->toBeString()
        ->toContain('structured fields')
        ->toContain('Conversation to Summarize');
});

it('defines structured summary schema fields', function (): void {
    $agent = new ConversationSummarizerAgent();
    $schema = $agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys([
        'summary',
        'topics',
        'key_facts',
        'unresolved_threads',
        'resolved_threads',
    ]);

    expect($schema['summary']->toArray())->toMatchArray(['type' => 'string'])
        ->and($schema['topics']->toArray())->toMatchArray([
            'type' => 'array',
            'items' => ['type' => 'string'],
        ])
        ->and($schema['key_facts']->toArray())->toMatchArray([
            'type' => 'array',
            'items' => ['type' => 'string'],
        ])
        ->and($schema['unresolved_threads']->toArray())->toMatchArray([
            'type' => 'array',
            'items' => ['type' => 'string'],
        ])
        ->and($schema['resolved_threads']->toArray())->toMatchArray([
            'type' => 'array',
            'items' => ['type' => 'string'],
        ]);
});
