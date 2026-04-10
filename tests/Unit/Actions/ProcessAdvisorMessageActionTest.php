<?php

declare(strict_types=1);

use App\Actions\ProcessAdvisorMessageAction;
use App\Ai\Agents\AgentRunner;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

covers(ProcessAdvisorMessageAction::class);

function fakeConversationStore(?string $latestId = null, string $newId = 'conv-123'): ConversationStore
{
    return new readonly class($latestId, $newId) implements ConversationStore
    {
        public function __construct(
            private ?string $latestId,
            private string $newId,
        ) {}

        public function latestConversationId(string|int $userId): ?string
        {
            return $this->latestId;
        }

        public function storeConversation(string|int|null $userId, string $title): string
        {
            return $this->newId;
        }

        public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
        {
            return 'msg-1';
        }

        public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
        {
            return 'msg-2';
        }

        public function getLatestConversationMessages(string $conversationId, int $limit): Collection
        {
            return collect();
        }
    };
}

it('creates new conversation when none exists', function (): void {
    AgentRunner::fake(['Hello!']);

    $action = new ProcessAdvisorMessageAction(
        resolve(AgentRunner::class),
        fakeConversationStore(),
    );

    $user = User::factory()->create();
    $result = $action->handle($user, 'Test message');

    expect($result['response'])->toBe('Hello!')
        ->and($result['conversation_id'])->toBe('conv-123');
    AgentRunner::assertPrompted('Test message');
});

it('uses existing conversation when provided', function (): void {
    AgentRunner::fake(['Continuing...']);

    $action = new ProcessAdvisorMessageAction(
        resolve(AgentRunner::class),
        resolve(ConversationStore::class),
    );

    $user = User::factory()->create();
    $result = $action->handle($user, 'Another message', 'existing-conv');

    expect($result['response'])->toBe('Continuing...')
        ->and($result['conversation_id'])->toBe('existing-conv');
});

it('reuses latest conversation when no id provided but exists', function (): void {
    AgentRunner::fake(['Reusing!']);

    $action = new ProcessAdvisorMessageAction(
        resolve(AgentRunner::class),
        fakeConversationStore(latestId: 'latest-conv'),
    );

    $user = User::factory()->create();
    $result = $action->handle($user, 'Message');

    expect($result['response'])->toBe('Reusing!')
        ->and($result['conversation_id'])->toBe('latest-conv');
});

it('resets conversation', function (): void {
    $action = new ProcessAdvisorMessageAction(
        resolve(AgentRunner::class),
        fakeConversationStore(newId: 'new-conv'),
    );

    $user = User::factory()->create();

    expect($action->resetConversation($user))->toBe('new-conv');
});
