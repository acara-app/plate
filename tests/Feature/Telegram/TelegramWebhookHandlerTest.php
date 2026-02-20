<?php

declare(strict_types=1);

use App\Contracts\ParsesHealthData;
use App\Contracts\ProcessesAdvisorMessage;
use App\Contracts\SavesHealthLog;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use App\Enums\Sex;
use App\Exceptions\TelegramUserException;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserTelegramChat;
use Carbon\CarbonInterface;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Testing\TestResponse;
use Tests\Fixtures\TelegramWebhookPayloads;

beforeEach(function (): void {
    Telegraph::fake();

    $this->bot = TelegraphBot::factory()->create();
    $this->telegraphChat = TelegraphChat::factory()->for($this->bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);

    // Create a simple test implementation of ParsesHealthData
    $parserMock = new class implements ParsesHealthData
    {
        public HealthLogData $returnValue;

        public function __construct()
        {
            $this->returnValue = new HealthLogData(
                isHealthData: false,
                logType: HealthEntryType::Glucose
            );
        }

        public function parse(string $message): HealthLogData
        {
            return $this->returnValue;
        }
    };

    app()->instance(ParsesHealthData::class, $parserMock);
});

function sendWebhook(mixed $test, string $text): TestResponse
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

        $mock = new class implements ProcessesAdvisorMessage
        {
            public string $resetConversationReturn = 'new-conv-id';

            public array $calls = [];

            public function handle(User $user, string $message, ?string $conversationId = null): array
            {
                $this->calls[] = ['method' => 'handle', 'user' => $user->id, 'message' => $message];

                return ['response' => 'Test', 'conversation_id' => 'conv-123'];
            }

            public function resetConversation(User $user): string
            {
                $this->calls[] = ['method' => 'resetConversation', 'user' => $user->id];

                return $this->resetConversationReturn;
            }
        };

        app()->instance(ProcessesAdvisorMessage::class, $mock);

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

        // Create a simple test implementation
        $mock = new class implements ProcessesAdvisorMessage
        {
            public string $resetConversationReturn = 'reset-conv-id';

            public function handle(User $user, string $message, ?string $conversationId = null): array
            {
                return ['response' => 'Test', 'conversation_id' => 'conv-123'];
            }

            public function resetConversation(User $user): string
            {
                return $this->resetConversationReturn;
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

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => 'existing-conv',
        ]);

        // Create a simple test implementation
        $mock = new class implements ProcessesAdvisorMessage
        {
            public array $calls = [];

            public function handle(User $user, string $message, ?string $conversationId = null): array
            {
                $this->calls[] = [
                    'method' => 'handle',
                    'user' => $user->id,
                    'message' => $message,
                    'conversationId' => $conversationId,
                ];

                return [
                    'response' => 'Here are some breakfast suggestions...',
                    'conversation_id' => 'existing-conv',
                ];
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

        $linkedChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => null,
        ]);

        // Create a simple test implementation
        $mock = new class implements ProcessesAdvisorMessage
        {
            public array $calls = [];

            public function handle(User $user, string $message, ?string $conversationId = null): array
            {
                $this->calls[] = [
                    'method' => 'handle',
                    'user' => $user->id,
                    'message' => $message,
                    'conversationId' => $conversationId,
                ];

                return [
                    'response' => 'Welcome!',
                    'conversation_id' => 'first-conv-id',
                ];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };

        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Hello!');

        expect($linkedChat->fresh()->conversation_id)->toBe('first-conv-id');
    });

    it('does not overwrite existing conversation id', function (): void {
        $user = User::factory()->create();

        $linkedChat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'conversation_id' => 'existing-conv',
        ]);

        // Create a simple test implementation
        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null): array
            {
                return [
                    'response' => 'Response',
                    'conversation_id' => 'some-new-conv',
                ];
            }

            public function resetConversation(User $user): string
            {
                return 'new-conv';
            }
        };

        app()->instance(ProcessesAdvisorMessage::class, $mock);

        sendWebhook($this, 'Follow-up message');

        expect($linkedChat->fresh()->conversation_id)->toBe('existing-conv');
    });

    it('handles AI response errors gracefully', function (): void {
        $user = User::factory()->create();

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        // Create a simple test implementation that throws exception
        $mock = new class implements ProcessesAdvisorMessage
        {
            public function handle(User $user, string $message, ?string $conversationId = null): array
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

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        // Update the ParsesHealthData mock to throw exception
        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                throw new TelegramUserException('User error occurred');
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        sendWebhook($this, 'Invalid input');

        Telegraph::assertSent('User error occurred');
    });

    it('handles generic Throwable in handleChatMessage gracefully', function (): void {
        $user = User::factory()->create();

        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        // Update the ParsesHealthData mock to throw generic exception
        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                throw new Exception('Unexpected error');
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        sendWebhook($this, 'Something went wrong');

        Telegraph::assertSent('❌ Error processing message. Please try again.');
    });
});

describe('/yes command', function (): void {
    it('replies error when not linked', function (): void {
        sendWebhook($this, '/yes');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('replies error when no pending log exists', function (): void {
        $user = User::factory()->create();
        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/yes');

        Telegraph::assertSent('❌ No pending log to confirm. Just tell me what you want to log!', false);
    });

    it('confirms pending log and saves it', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
                'glucose_unit' => 'mg/dL',
                'measured_at' => now()->toISOString(),
            ],
        ]);

        // Create a simple test implementation that captures calls
        $saveActionMock = new class implements SavesHealthLog
        {
            public array $calls = [];

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->calls[] = [
                    'user' => $user->id,
                    'data' => $data,
                    'measuredAt' => $measuredAt,
                ];
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, '/yes');

        expect($chat->fresh()->pending_health_log)->toBeNull();
        expect($saveActionMock->calls)->toHaveCount(1);
        expect($saveActionMock->calls[0]['data']->glucoseValue)->toBe(120.0);
        expect($saveActionMock->calls[0]['data']->logType)->toBe(HealthEntryType::Glucose);
        Telegraph::assertSent('✅ Saved! Your health data has been logged.', false);
    });

    it('handles save error gracefully', function (): void {
        $user = User::factory()->create();
        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
            ],
        ]);

        // Create a simple test implementation that throws exception
        $saveActionMock = new class implements SavesHealthLog
        {
            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                throw new Exception('Save failed');
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, '/yes');

        Telegraph::assertSent('❌ Error saving log. Please try again.', false);
    });
});

describe('/no command', function (): void {
    it('replies error when not linked', function (): void {
        sendWebhook($this, '/no');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('replies error when no pending log exists', function (): void {
        $user = User::factory()->create();
        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        sendWebhook($this, '/no');

        Telegraph::assertSent('❌ No pending log to cancel.', false);
    });

    it('clears pending log', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
            ],
        ]);

        sendWebhook($this, '/no');

        expect($chat->fresh()->pending_health_log)->toBeNull();
        Telegraph::assertSent('❌ Log discarded. Tell me if you want to log something else!', false);
    });
});

describe('health log flow', function (): void {
    it('detects health log intent and asks for confirmation', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
        ]);

        // Update the ParsesHealthData mock to return health data
        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                return new HealthLogData(
                    isHealthData: true,
                    logType: HealthEntryType::Glucose,
                    glucoseValue: 120.0,
                    glucoseUnit: GlucoseUnit::MgDl
                );
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        sendWebhook($this, 'Glucose 120');

        $chat->refresh();
        expect($chat->pending_health_log)->not->toBeNull()
            ->and($chat->pending_health_log['glucose_value'])->toEqual(120);

        Telegraph::assertSent('📝 Log: Glucose 120 mg/dL', false);
    });

    it('handles yes confirmation via text', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
            ],
        ]);

        // Create a simple test implementation
        $saveActionMock = new class implements SavesHealthLog
        {
            public bool $handleCalled = false;

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->handleCalled = true;
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, 'yes');

        expect($chat->fresh()->pending_health_log)->toBeNull();
        expect($saveActionMock->handleCalled)->toBeTrue();
        Telegraph::assertSent('✅ Saved!', false);
    });

    it('handles no cancellation via text', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
            ],
        ]);

        sendWebhook($this, 'no');

        expect($chat->fresh()->pending_health_log)->toBeNull();
        Telegraph::assertSent('❌ Log discarded.', false);
    });

    it('handles error when processing pending log message', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => ['is_health_data' => true],
        ]);

        // Update the ParsesHealthData mock to throw exception
        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                throw new Exception('Parsing error');
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        sendWebhook($this, 'Invalid input');

        Telegraph::assertSent('❌ Could not understand that. Try something like: "My glucose is 140" or "Took 5 units insulin"');
    });

    it('reconstructs complex health log data correctly', function (): void {
        $user = User::factory()->create();
        // Create user chat but don't set pending log yet to allow clean setup in test logic if needed,
        // but here we need it set.
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'insulin',
                'insulin_units' => 5.5,
                'insulin_type' => 'bolus',
                'medication_name' => 'Metformin',
                'medication_dosage' => '500mg',
                'glucose_value' => null, // ensure nullable fields work
            ],
        ]);

        // Create a simple test implementation that captures calls
        $saveActionMock = new class implements SavesHealthLog
        {
            public array $calls = [];

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->calls[] = [
                    'user' => $user->id,
                    'data' => $data,
                ];
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, 'yes');

        expect($chat->fresh()->pending_health_log)->toBeNull();
        expect($saveActionMock->calls)->toHaveCount(1);
        expect($saveActionMock->calls[0]['data']->logType)->toBe(HealthEntryType::Insulin);
        expect($saveActionMock->calls[0]['data']->insulinUnits)->toBe(5.5);
        expect($saveActionMock->calls[0]['data']->insulinType)->toBe(InsulinType::Bolus);
        expect($saveActionMock->calls[0]['data']->medicationName)->toBe('Metformin');
        expect($saveActionMock->calls[0]['data']->medicationDosage)->toBe('500mg');
        Telegraph::assertSent('✅ Saved! Your health data has been logged.', false);
    });

    it('catches TelegramUserException during pending log state', function (): void {
        $user = User::factory()->create();
        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => ['is_health_data' => true],
        ]);

        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                throw new TelegramUserException('Custom user error');
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        sendWebhook($this, 'Invalid input');

        Telegraph::assertSent('Custom user error');
    });

    it('reconstructs HealthLogData with all enums and dates', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
                'glucose_reading_type' => GlucoseReadingType::PostMeal->value,
                'glucose_unit' => 'mg/dL',
                'insulin_type' => InsulinType::Bolus->value,
                'measured_at' => '2023-10-27T10:00:00.000000Z',
                'medication_name' => 123,
            ],
        ]);

        $saveActionMock = new class implements SavesHealthLog
        {
            public array $calls = [];

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->calls[] = ['data' => $data];
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, 'yes');

        expect($saveActionMock->calls)->toHaveCount(1);
        $data = $saveActionMock->calls[0]['data'];

        expect($data->glucoseReadingType)->toBe(GlucoseReadingType::PostMeal);
        expect($data->insulinType)->toBe(InsulinType::Bolus);
        expect($data->measuredAt)->not->toBeNull();
        expect($data->measuredAt->toIsoString())->toContain('2023-10-27T10:00:00');
        expect($data->medicationName)->toBe('123');
    });

    it('handles scalar values in toStringOrNull', function (): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'medication_name' => true,
            ],
        ]);

        $saveActionMock = new class implements SavesHealthLog
        {
            public array $calls = [];

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->calls[] = ['data' => $data];
            }
        };
        app()->instance(SavesHealthLog::class, $saveActionMock);

        sendWebhook($this, 'yes');

        expect($saveActionMock->calls[0]['data']->medicationName)->toBe('1');
    });
});
