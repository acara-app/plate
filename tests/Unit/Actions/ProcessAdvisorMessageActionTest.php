<?php

declare(strict_types=1);

use App\Actions\ProcessAdvisorMessageAction;
use App\Ai\Agents\AssistantAgent;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

it('creates new conversation when none exists', function (): void {
    AssistantAgent::fake(['Hello!']);

    // Create a simple test implementation of ConversationStore
    $conversationStore = new class implements ConversationStore
    {
        public ?string $latestConversationIdReturn = null;

        public ?string $storedConversationId = null;

        public array $calls = [];

        public function latestConversationId(string|int $userId): ?string
        {
            $this->calls[] = ['method' => 'latestConversationId', 'userId' => $userId];

            return $this->latestConversationIdReturn;
        }

        public function storeConversation(string|int|null $userId, string $title): string
        {
            $this->calls[] = ['method' => 'storeConversation', 'userId' => $userId, 'title' => $title];
            $this->storedConversationId = 'conv-123';

            return $this->storedConversationId;
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

    $action = new ProcessAdvisorMessageAction(
        resolve(AssistantAgent::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Test message');

    expect($result['response'])->toBe('Hello!');
    expect($result['conversation_id'])->toBe('conv-123');
    AssistantAgent::assertPrompted('Test message');
});

it('uses existing conversation when provided', function (): void {
    AssistantAgent::fake(['Continuing...']);

    $action = new ProcessAdvisorMessageAction(
        resolve(AssistantAgent::class),
        resolve(ConversationStore::class),
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Another message', 'existing-conv');

    expect($result['response'])->toBe('Continuing...');
    expect($result['conversation_id'])->toBe('existing-conv');
});

it('reuses latest conversation when no id provided but exists', function (): void {
    AssistantAgent::fake(['Reusing!']);

    // Create a simple test implementation that returns an existing conversation
    $conversationStore = new class implements ConversationStore
    {
        public ?string $latestConversationIdReturn = 'latest-conv';

        public function latestConversationId(string|int $userId): ?string
        {
            return $this->latestConversationIdReturn;
        }

        public function storeConversation(string|int|null $userId, string $title): string
        {
            return 'new-conv';
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

    $action = new ProcessAdvisorMessageAction(
        resolve(AssistantAgent::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Message');

    expect($result['response'])->toBe('Reusing!');
    expect($result['conversation_id'])->toBe('latest-conv');
});

it('resets conversation', function (): void {
    // Create a simple test implementation
    $conversationStore = new class implements ConversationStore
    {
        public ?string $storedConversationId = null;

        public function latestConversationId(string|int $userId): ?string
        {
            return null;
        }

        public function storeConversation(string|int|null $userId, string $title): string
        {
            $this->storedConversationId = 'new-conv';

            return $this->storedConversationId;
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

    $action = new ProcessAdvisorMessageAction(
        resolve(AssistantAgent::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->resetConversation($user);

    expect($result)->toBe('new-conv');
});
