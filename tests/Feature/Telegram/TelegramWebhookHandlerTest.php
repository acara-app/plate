<?php

declare(strict_types=1);

use App\Contracts\DownloadsTelegramPhoto;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\ChatPlatform;
use App\Enums\Sex;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Exceptions\TelegramUserException;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use App\Models\UserProfile;
use App\Services\Telegram\TelegramWebhookHandler;
use App\Utilities\StaticUrl;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Files\Base64Image;
use Tests\Fixtures\TelegramWebhookPayloads;

covers(TelegramWebhookHandler::class);

beforeEach(function (): void {
    Telegraph::fake();

    $this->bot = TelegraphBot::factory()->create();
    $this->telegraphChat = TelegraphChat::factory()->for($this->bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);
});

function sendWebhook(mixed $test, string $text): TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::message($text, (string) $test->telegraphChat->chat_id),
    );
}

function sendPhotoWebhook(mixed $test, string $caption = ''): TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::photoMessage(
            chatId: (string) $test->telegraphChat->chat_id,
            caption: $caption,
        ),
    );
}

function linkedChatFor(mixed $test, User $user, array $overrides = []): UserChatPlatformLink
{
    return UserChatPlatformLink::factory()
        ->linked($user)
        ->create(array_merge([
            'platform' => ChatPlatform::Telegram,
            'platform_user_id' => (string) $test->telegraphChat->chat_id,
        ], $overrides));
}

function sendCallback(mixed $test, string $action, string $toolCallId = 'call_abc'): TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::callbackQuery(
            $action,
            mb_substr(sha1($toolCallId), 0, 12),
            (string) $test->telegraphChat->chat_id,
        ),
    );
}

function telegramAdvisor(callable $handle, ?callable $resume = null): ProcessesAdvisorMessage
{
    return new class($handle, $resume) implements ProcessesAdvisorMessage
    {
        /** @var callable */
        private $handle;

        /** @var ?callable */
        private $resumeHandler;

        public function __construct(callable $handle, ?callable $resume)
        {
            $this->handle = $handle;
            $this->resumeHandler = $resume;
        }

        public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
        {
            return ($this->handle)($user, $message, $conversationId, $attachments);
        }

        public function resume(User $user, string $conversationId, Decisions $decisions): array
        {
            return ($this->resumeHandler)($user, $conversationId, $decisions);
        }

        public function resetConversation(User $user): string
        {
            return 'reset';
        }
    };
}

function pausedTurnFor(User $user, string $conversationId, ?array $pending = null): Conversation
{
    $pending ??= ['call_abc' => 'Glucose 140 mg/dL (fasting)'];

    $conversation = Conversation::factory()->forUser($user)->create(['id' => $conversationId]);

    History::factory()
        ->forConversation($conversation)
        ->awaitingApproval($pending)
        ->create([
            'tool_calls' => collect($pending)
                ->map(fn (string $reason, string $id): array => ['id' => $id, 'name' => 'log_health_entry', 'arguments' => ['log_type' => 'glucose']])
                ->values()
                ->all(),
        ]);

    return $conversation;
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
        $user = User::factory()->create();
        UserChatPlatformLink::factory()->create([
            'user_id' => $user->id,
            'platform' => ChatPlatform::Telegram,
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

        $pending = UserChatPlatformLink::factory()->create([
            'user_id' => $user->id,
            'platform' => ChatPlatform::Telegram,
            'linking_token' => 'ABCD1234',
            'token_expires_at' => now()->addHours(24),
            'is_active' => true,
            'linked_at' => null,
        ]);

        sendWebhook($this, '/link abcd1234');

        $fresh = $pending->fresh();
        expect($fresh->platform_user_id)->toBe((string) $this->telegraphChat->chat_id)
            ->and($fresh->is_active)->toBeTrue()
            ->and($fresh->linked_at)->not->toBeNull()
            ->and($fresh->linking_token)->toBeNull();

        Telegraph::assertSent('✅ Linked!', false);
    });

    it('removes prior links for the same platform user id when a new user links', function (): void {
        $previousUser = User::factory()->create();
        $existing = linkedChatFor($this, $previousUser);

        $newUser = User::factory()->create();
        UserChatPlatformLink::factory()->create([
            'user_id' => $newUser->id,
            'platform' => ChatPlatform::Telegram,
            'linking_token' => 'NEWTOKE1',
            'token_expires_at' => now()->addHours(24),
            'is_active' => true,
            'linked_at' => null,
        ]);

        sendWebhook($this, '/link NEWTOKE1');

        expect(UserChatPlatformLink::query()->find($existing->id))->toBeNull();
    });
});

describe('/me command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendWebhook($this, '/me');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('shows basic user info without profile', function (): void {
        $user = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        linkedChatFor($this, $user);

        sendWebhook($this, '/me');

        Telegraph::assertSent('👤 Alice', false);
        Telegraph::assertSent('📧 alice@example.com', false);
    });

    it('shows user info with full profile', function (): void {
        $user = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        UserProfile::factory()->for($user)->create([
            'age' => 30, 'height' => 180, 'weight' => 75, 'sex' => Sex::Male,
        ]);
        linkedChatFor($this, $user);

        sendWebhook($this, '/me');

        Telegraph::assertSent('30 years, Male', false);
        Telegraph::assertSent('180cm, 75kg', false);
    });

    it('handles profile with all null fields gracefully', function (): void {
        $user = User::factory()->create(['name' => 'Carol']);
        UserProfile::factory()->for($user)->create([
            'age' => null, 'height' => null, 'weight' => null, 'sex' => null,
        ]);
        linkedChatFor($this, $user);

        sendWebhook($this, '/me');

        Telegraph::assertSent('N/A, N/A', false);
    });

    it('handles profile with partial null fields', function (): void {
        $user = User::factory()->create(['name' => 'Dave']);
        UserProfile::factory()->for($user)->create([
            'age' => 25, 'height' => null, 'weight' => 80, 'sex' => Sex::Female,
        ]);
        linkedChatFor($this, $user);

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

    it('resets conversation and updates the link record', function (): void {
        $user = User::factory()->create();
        $link = linkedChatFor($this, $user, ['conversation_id' => 'old-conv-id']);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                return ['response' => 'Test', 'conversation_id' => 'conv-123', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv-id';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, '/new');

        expect($link->fresh()->conversation_id)->toBe('new-conv-id');
        Telegraph::assertSent('✨ New conversation started! How can I help you?');
    });
});

describe('/reset command', function (): void {
    it('delegates to /new command behavior', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                return ['response' => 'Test', 'conversation_id' => 'conv-123', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'reset-conv-id';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

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
        linkedChatFor($this, $user, ['conversation_id' => 'existing-conv']);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                return ['response' => 'Here are some breakfast suggestions...', 'conversation_id' => 'existing-conv', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'What should I eat for breakfast?');

        Telegraph::assertSent('Here are some breakfast suggestions...', false);
    });

    it('stores conversation id on first message', function (): void {
        $user = User::factory()->create();
        $link = linkedChatFor($this, $user, ['conversation_id' => null]);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                return ['response' => 'Welcome!', 'conversation_id' => 'first-conv-id', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Hello!');

        expect($link->fresh()->conversation_id)->toBe('first-conv-id');
    });

    it('does not overwrite existing conversation id', function (): void {
        $user = User::factory()->create();
        $link = linkedChatFor($this, $user, ['conversation_id' => 'existing-conv']);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                return ['response' => 'Response', 'conversation_id' => 'existing-conv', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Follow-up message');

        expect($link->fresh()->conversation_id)->toBe('existing-conv');
    });

    it('handles AI response errors gracefully', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                throw new Exception('AI service unavailable');
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Hello');

        Telegraph::assertSent('❌ Error processing message. Please try again.');
    });

    it('handles TelegramUserException gracefully', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                throw new TelegramUserException('User error occurred');
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Invalid input');

        Telegraph::assertSent('User error occurred');
    });

    it('shows a friendly upgrade message when the AI credit limit is exceeded', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                throw new UsageLimitExceededException(
                    limitType: 'rolling',
                    tier: SubscriptionTier::Free,
                    currentCredits: 119,
                    limitCredits: 100,
                    resetsAt: now()->addHours(18)->addMinutes(45),
                );
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'What should I eat?');

        Telegraph::assertSent('daily AI credits', false);
        Telegraph::assertSent('Free plan', false);
        Telegraph::assertSent(StaticUrl::checkoutUrl(), false);
    });
});

describe('photo message handling', function (): void {
    it('processes photo with caption and passes attachments', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'existing-conv']);

        $calls = [];
        $mock = new class($calls) implements ProcessesAdvisorMessage
        {
            public function __construct(public array &$calls) {}

            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                $this->calls[] = ['message' => $message, 'attachmentCount' => count($attachments)];

                return ['response' => 'I analyzed your food photo!', 'conversation_id' => 'existing-conv', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        $downloadAction = Mockery::mock(DownloadsTelegramPhoto::class);
        $downloadAction->shouldReceive('handle')->once()->andReturn(new Base64Image(base64_encode('fake-image'), 'image/jpeg'));
        app()->instance(DownloadsTelegramPhoto::class, $downloadAction);

        sendPhotoWebhook($this, 'What is this meal?');

        Telegraph::assertSent('I analyzed your food photo!', false);
        expect($calls)->toHaveCount(1)
            ->and($calls[0]['message'])->toBe('What is this meal?')
            ->and($calls[0]['attachmentCount'])->toBe(1);
    });

    it('uses default message when photo has no caption', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'existing-conv']);

        $calls = [];
        $mock = new class($calls) implements ProcessesAdvisorMessage
        {
            public function __construct(public array &$calls) {}

            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                $this->calls[] = ['message' => $message, 'attachmentCount' => count($attachments)];

                return ['response' => 'Analyzed!', 'conversation_id' => 'existing-conv', 'pending_approvals' => []];
            }

            public function resume(User $user, string $conversationId, Decisions $decisions): array
            {
                return ['response' => '', 'conversation_id' => $conversationId, 'pending_approvals' => []];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        $downloadAction = Mockery::mock(DownloadsTelegramPhoto::class);
        $downloadAction->shouldReceive('handle')->once()->andReturn(new Base64Image(base64_encode('fake-image'), 'image/jpeg'));
        app()->instance(DownloadsTelegramPhoto::class, $downloadAction);

        sendPhotoWebhook($this);

        expect($calls[0]['message'])->toBe('Analyze this food photo and log it.')
            ->and($calls[0]['attachmentCount'])->toBe(1);
    });

    it('handles photo download failure gracefully', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        $downloadAction = Mockery::mock(DownloadsTelegramPhoto::class);
        $downloadAction->shouldReceive('handle')->once()->andThrow(new RuntimeException('Download failed'));
        app()->instance(DownloadsTelegramPhoto::class, $downloadAction);

        sendPhotoWebhook($this, 'Analyze this');

        Telegraph::assertSent('❌ Error processing message. Please try again.');
    });

    it('replies not linked when no active link exists for photo message', function (): void {
        $downloadAction = Mockery::mock(DownloadsTelegramPhoto::class);
        $downloadAction->shouldNotReceive('handle');

        app()->instance(DownloadsTelegramPhoto::class, $downloadAction);

        sendPhotoWebhook($this);

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });
});

describe('health-log approval card', function (): void {
    it('sends an approval card with the summary and a not-saved-yet status when the agent pauses a log', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);

        app()->instance(ProcessesAdvisorMessage::class, telegramAdvisor(
            fn (): array => [
                'response' => 'Here is what I will log:',
                'conversation_id' => 'conv-x',
                'pending_approvals' => [new PendingApproval(
                    id: 'call_abc',
                    tool: 'log_health_entry',
                    arguments: ['log_type' => 'glucose'],
                    reason: 'Glucose 140 mg/dL (fasting)',
                )],
            ],
        ));

        sendWebhook($this, 'log my glucose 140 fasting');

        Telegraph::assertSent('Here is what I will log:', false);
        Telegraph::assertSent('Glucose 140 mg/dL (fasting)', false);
        Telegraph::assertSent('Not saved yet', false);
    });
});

describe('approval callbacks', function (): void {
    it('approves the paused call, resumes the turn, and shows the saved status', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        pausedTurnFor($user, 'conv-x');

        $resumed = null;
        app()->instance(ProcessesAdvisorMessage::class, telegramAdvisor(
            fn (): array => ['response' => '', 'conversation_id' => 'conv-x', 'pending_approvals' => []],
            function (User $u, string $conversationId, Decisions $decisions) use (&$resumed): array {
                $resumed = $decisions;

                return ['response' => 'Logged 140 mg/dL.', 'conversation_id' => 'conv-x', 'pending_approvals' => []];
            },
        ));

        sendCallback($this, 'approve');

        expect($resumed?->get('call_abc')?->isApproved())->toBeTrue();
        Telegraph::assertSentData('editMessageText', ['text' => 'Saved'], false);
        Telegraph::assertSent('Logged 140 mg/dL.', false);
    });

    it('rejects the paused call and tells the model nothing was saved', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        pausedTurnFor($user, 'conv-x');

        $resumed = null;
        app()->instance(ProcessesAdvisorMessage::class, telegramAdvisor(
            fn (): array => ['response' => '', 'conversation_id' => 'conv-x', 'pending_approvals' => []],
            function (User $u, string $conversationId, Decisions $decisions) use (&$resumed): array {
                $resumed = $decisions;

                return ['response' => 'Understood, nothing saved.', 'conversation_id' => 'conv-x', 'pending_approvals' => []];
            },
        ));

        sendCallback($this, 'reject');

        expect($resumed?->get('call_abc')?->isRejected())->toBeTrue();
        Telegraph::assertSentData('editMessageText', ['text' => 'Dismissed'], false);
    });

    it('replies neutrally when the conversation has nothing awaiting a decision', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        Conversation::factory()->forUser($user)->create(['id' => 'conv-x']);

        sendCallback($this, 'approve');

        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'no longer available'], false);
    });

    it('refuses a card whose tool call is no longer the one awaiting a decision', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        pausedTurnFor($user, 'conv-x');

        sendCallback($this, 'approve', 'call_from_an_older_turn');

        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'no longer available'], false);
    });

    it('records one decision and waits when the turn paused on several calls', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        pausedTurnFor($user, 'conv-x', pending: ['call_abc' => 'Eggs', 'call_def' => 'Coffee']);

        $resumed = false;
        app()->instance(ProcessesAdvisorMessage::class, telegramAdvisor(
            fn (): array => ['response' => '', 'conversation_id' => 'conv-x', 'pending_approvals' => []],
            function () use (&$resumed): array {
                $resumed = true;

                return ['response' => 'done', 'conversation_id' => 'conv-x', 'pending_approvals' => []];
            },
        ));

        sendCallback($this, 'approve', 'call_abc');

        expect($resumed)->toBeFalse();
        Telegraph::assertSentData('editMessageText', ['text' => 'Recorded'], false);

        sendCallback($this, 'reject', 'call_def');

        expect($resumed)->toBeTrue();
    });

    it('does not report a save when the resumed turn throws', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);
        pausedTurnFor($user, 'conv-x');

        app()->instance(ProcessesAdvisorMessage::class, telegramAdvisor(
            fn (): array => ['response' => '', 'conversation_id' => 'conv-x', 'pending_approvals' => []],
            fn () => throw new RuntimeException('provider exploded'),
        ));

        sendCallback($this, 'approve');

        Telegraph::assertSentData('editMessageText', ['text' => 'Could not be saved'], false);
    });

    it('asks an unlinked user to link their account first', function (): void {
        sendCallback($this, 'approve');

        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'Please link your account'], false);
    });
});
