<?php

declare(strict_types=1);

use App\Contracts\DownloadsTelegramPhoto;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\AgentApprovalStatus;
use App\Enums\ChatPlatform;
use App\Enums\Sex;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Exceptions\TelegramUserException;
use App\Jobs\ExecuteApprovalJob;
use App\Models\AgentApproval;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use App\Models\UserProfile;
use App\Services\Telegram\TelegramWebhookHandler;
use App\Utilities\StaticUrl;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
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

function sendCallback(mixed $test, string $action, string $approvalId): TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::callbackQuery($action, $approvalId, (string) $test->telegraphChat->chat_id),
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
                return ['response' => 'Test', 'conversation_id' => 'conv-123'];
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
                return ['response' => 'Test', 'conversation_id' => 'conv-123'];
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
                return ['response' => 'Here are some breakfast suggestions...', 'conversation_id' => 'existing-conv'];
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
                return ['response' => 'Welcome!', 'conversation_id' => 'first-conv-id'];
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
                return ['response' => 'Response', 'conversation_id' => 'existing-conv'];
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

                return ['response' => 'I analyzed your food photo!', 'conversation_id' => 'existing-conv'];
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

                return ['response' => 'Analyzed!', 'conversation_id' => 'existing-conv'];
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
    it('sends an approval card with the summary and a not-saved-yet status when the agent proposes a log', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user, ['conversation_id' => 'conv-x']);

        $approval = AgentApproval::factory()->telegram()->create([
            'user_id' => $user->id,
            'summary' => 'Glucose 140 mg/dL (fasting)',
        ]);

        $mock = new readonly class($approval->id) implements ProcessesAdvisorMessage
        {
            public function __construct(private string $approvalId) {}

            public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
            {
                Context::push('chat.created_approvals', $this->approvalId);

                return ['response' => 'Here is what I will log:', 'conversation_id' => 'conv-x'];
            }

            public function resetConversation(User $user): string
            {
                return 'x';
            }
        };
        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'log my glucose 140 fasting');

        Telegraph::assertSent('Here is what I will log:', false);
        Telegraph::assertSent('Glucose 140 mg/dL (fasting)', false);
        Telegraph::assertSent('Not saved yet', false);
    });
});

describe('approval callbacks', function (): void {
    it('approves the entry, queues execution, and shows a saving status', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        linkedChatFor($this, $user);
        $approval = AgentApproval::factory()->telegram()->create(['user_id' => $user->id]);

        sendCallback($this, 'approve', $approval->id);

        expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Approved);
        Queue::assertPushed(ExecuteApprovalJob::class, 1);
        Telegraph::assertSentData('editMessageText', ['text' => 'Saving your entry'], false);
    });

    it('rejects the entry without queuing execution', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        linkedChatFor($this, $user);
        $approval = AgentApproval::factory()->telegram()->create(['user_id' => $user->id]);

        sendCallback($this, 'reject', $approval->id);

        expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Rejected);
        Queue::assertNotPushed(ExecuteApprovalJob::class);
        Telegraph::assertSentData('editMessageText', ['text' => 'Not saved'], false);
    });

    it('refuses to act on an approval owned by another user', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        $other = User::factory()->create();
        linkedChatFor($this, $user);
        $approval = AgentApproval::factory()->telegram()->create(['user_id' => $other->id]);

        sendCallback($this, 'approve', $approval->id);

        expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
        Queue::assertNotPushed(ExecuteApprovalJob::class);
        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'no longer available'], false);
    });

    it('replies neutrally for an unknown approval id', function (): void {
        $user = User::factory()->create();
        linkedChatFor($this, $user);

        sendCallback($this, 'approve', '00000000-0000-0000-0000-000000000000');

        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'no longer available'], false);
    });

    it('asks an unlinked user to link their account first', function (): void {
        $approval = AgentApproval::factory()->telegram()->create();

        sendCallback($this, 'approve', $approval->id);

        Telegraph::assertSentData('answerCallbackQuery', ['text' => 'Please link your account'], false);
        expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
    });
});
