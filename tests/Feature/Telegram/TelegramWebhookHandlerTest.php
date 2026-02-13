<?php

declare(strict_types=1);

use App\Contracts\GeneratesAiResponse;
use App\Contracts\ParsesHealthData;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserTelegramChat;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Tests\Fixtures\TelegramWebhookPayloads;

beforeEach(function (): void {
    Telegraph::fake();

    $this->bot = TelegraphBot::factory()->create();
    $this->telegraphChat = TelegraphChat::factory()->for($this->bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);

    $parserMock = Mockery::mock(ParsesHealthData::class);
    $parserMock->shouldReceive('parse')->andReturn(new App\DataObjects\HealthLogData(isHealthData: false, logType: 'glucose'));
    app()->instance(ParsesHealthData::class, $parserMock);
});

function sendWebhook(mixed $test, string $text): Illuminate\Testing\TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::message($text, (string) $test->telegraphChat->chat_id),
    );
}

describe('/start command', function (): void {
    it('sends a welcome message', function (): void {
        sendWebhook($this, '/start');

        Telegraph::assertSent('👋 Welcome to Acara Plate!', false);
    });

    it('includes all available commands in the message', function (): void {
        sendWebhook($this, '/start');

        Telegraph::assertSent('/new', false);
        Telegraph::assertSent('/me', false);
        Telegraph::assertSent('/help', false);
    });
});

describe('/help command', function (): void {
    it('sends the help message listing all commands', function (): void {
        sendWebhook($this, '/help');

        Telegraph::assertSent('📚 Available Commands:', false);
    });
});

describe('/link command', function (): void {
    it('rejects token with invalid length', function (): void {
        sendWebhook($this, '/link ABC');

        Telegraph::assertSent('❌ Invalid token. Use: /link ABC123XY');
    });

    it('rejects expired token', function (): void {
        UserTelegramChat::factory()->create([
            'linking_token' => 'ABCD1234',
            'token_expires_at' => now()->subHour(),
        ]);

        sendWebhook($this, '/link ABCD1234');

        Telegraph::assertSent('❌ Invalid or expired token.');
    });

    it('rejects non-existent token', function (): void {
        sendWebhook($this, '/link ZZZZ9999');

        Telegraph::assertSent('❌ Invalid or expired token.');
    });

    it('links account with a valid token', function (): void {
        $user = User::factory()->create(['name' => 'John']);

        $pendingChat = UserTelegramChat::factory()->for($user)->create([
            'telegraph_chat_id' => null,
            'linking_token' => 'ABCD1234',
            'token_expires_at' => now()->addHours(24),
            'is_active' => false,
            'linked_at' => null,
        ]);

        sendWebhook($this, '/link abcd1234');

        $pendingChat->refresh();

        expect($pendingChat->telegraph_chat_id)->toBe($this->telegraphChat->id)
            ->and($pendingChat->is_active)->toBeTrue()
            ->and($pendingChat->linked_at)->not->toBeNull()
            ->and($pendingChat->linking_token)->toBeNull();

        Telegraph::assertSent('✅ Linked!', false);
    });

    it('deactivates existing links for the same telegraph chat', function (): void {
        $existingUser = User::factory()->create();
        $existingChat = UserTelegramChat::factory()->for($existingUser)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'is_active' => true,
        ]);

        $newUser = User::factory()->create();
        UserTelegramChat::factory()->for($newUser)->create([
            'telegraph_chat_id' => null,
            'linking_token' => 'NEWTOKEN',
            'token_expires_at' => now()->addHours(24),
            'linked_at' => null,
        ]);

        sendWebhook($this, '/link NEWTOKEN');

        expect($existingChat->fresh()->is_active)->toBeFalse();
    });

    it('removes duplicate chats for the same user and telegraph chat', function (): void {
        $user = User::factory()->create();

        $duplicateChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'is_active' => true,
        ]);

        $pendingChat = UserTelegramChat::factory()->for($user)->create([
            'telegraph_chat_id' => null,
            'linking_token' => 'ABCD1234',
            'token_expires_at' => now()->addHours(24),
            'linked_at' => null,
        ]);

        sendWebhook($this, '/link ABCD1234');

        expect(UserTelegramChat::query()->find($duplicateChat->id))->toBeNull()
            ->and($pendingChat->fresh())->not->toBeNull();
    });
});

describe('/me command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendWebhook($this, '/me');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('shows basic user info without profile', function (): void {
        $user = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/me');

        Telegraph::assertSent('👤 Alice', false);
        Telegraph::assertSent('📧 alice@example.com', false);
    });

    it('shows user info with full profile', function (): void {
        $user = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        UserProfile::factory()->for($user)->create([
            'age' => 30,
            'height' => 180,
            'weight' => 75,
            'sex' => Sex::Male,
        ]);

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/me');

        Telegraph::assertSent('30 years, Male', false);
        Telegraph::assertSent('180cm, 75kg', false);
    });

    it('handles profile with all null fields gracefully', function (): void {
        $user = User::factory()->create(['name' => 'Carol']);

        UserProfile::factory()->for($user)->create([
            'age' => null,
            'height' => null,
            'weight' => null,
            'sex' => null,
        ]);

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/me');

        Telegraph::assertSent('N/A, N/A', false);
    });

    it('handles profile with partial null fields', function (): void {
        $user = User::factory()->create(['name' => 'Dave']);

        UserProfile::factory()->for($user)->create([
            'age' => 25,
            'height' => null,
            'weight' => 80,
            'sex' => Sex::Female,
        ]);

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/me');

        Telegraph::assertSent('25 years, Female', false);
        Telegraph::assertSent('N/A, 80kg', false);
    });
});

describe('/new command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendWebhook($this, '/new');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('resets conversation and updates the chat record', function (): void {
        $user = User::factory()->create();

        $linkedChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => 'old-conv-id',
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('resetConversation')
            ->once()
            ->with(Mockery::on(fn (User $u): bool => $u->id === $user->id))
            ->andReturn('new-conv-id');
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, '/new');

        expect($linkedChat->fresh()->conversation_id)->toBe('new-conv-id');
        Telegraph::assertSent('✨ New conversation started! How can I help you?');
    });
});

describe('/reset command', function (): void {
    it('delegates to new command behavior', function (): void {
        $user = User::factory()->create();

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('resetConversation')->once()->andReturn('reset-conv-id');
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, '/reset');

        Telegraph::assertSent('✨ New conversation started! How can I help you?');
    });
});

describe('chat message handling', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendWebhook($this, 'What should I eat for breakfast?');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('generates AI response and sends it', function (): void {
        $user = User::factory()->create();

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => 'existing-conv',
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn (User $u): bool => $u->id === $user->id),
                'What should I eat for breakfast?',
                'existing-conv',
            )
            ->andReturn([
                'response' => 'Here are some breakfast suggestions...',
                'conversation_id' => 'existing-conv',
            ]);
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, 'What should I eat for breakfast?');

        Telegraph::assertSent('Here are some breakfast suggestions...', false);
    });

    it('stores conversation id on first message', function (): void {
        $user = User::factory()->create();

        $linkedChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => null,
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn (User $u): bool => $u->id === $user->id),
                'Hello!',
                null,
            )
            ->andReturn([
                'response' => 'Welcome!',
                'conversation_id' => 'first-conv-id',
            ]);
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, 'Hello!');

        expect($linkedChat->fresh()->conversation_id)->toBe('first-conv-id');
    });

    it('does not overwrite existing conversation id', function (): void {
        $user = User::factory()->create();

        $linkedChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => 'existing-conv',
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('handle')
            ->once()
            ->andReturn([
                'response' => 'Response',
                'conversation_id' => 'some-new-conv',
            ]);
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, 'Follow-up message');

        expect($linkedChat->fresh()->conversation_id)->toBe('existing-conv');
    });

    it('handles AI response errors gracefully', function (): void {
        $user = User::factory()->create();

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        $mock = Mockery::mock(GeneratesAiResponse::class);
        $mock->shouldReceive('handle')
            ->andThrow(new Exception('AI service unavailable'));
        app()->instance(GeneratesAiResponse::class, $mock);

        sendWebhook($this, 'Hello');

        Telegraph::assertSent('❌ Error processing message. Please try again.');
    });
});
