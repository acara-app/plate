<?php

declare(strict_types=1);

use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use App\Models\User;
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

    // Mock ParsesHealthData interface for tests
    $parserMock = Mockery::mock(ParsesHealthData::class);
    $parserMock->shouldReceive('parse')->andThrow(new Exception('Should not be called unless testing health data'));
    app()->instance(ParsesHealthData::class, $parserMock);
});

function sendHealthWebhook(mixed $test, string $text): Illuminate\Testing\TestResponse
{
    return $test->postJson(
        route('telegraph.webhook', ['token' => $test->bot->token]),
        TelegramWebhookPayloads::message($text, (string) $test->telegraphChat->chat_id),
    );
}

function createLinkedChatForHealth(mixed $test, User $user): UserTelegramChat
{
    return UserTelegramChat::factory()->for($user)->linked()->create([
        'telegraph_chat_id' => $test->telegraphChat->id,
    ]);
}

describe('/log command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendHealthWebhook($this, '/log');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('shows health data logging instructions when linked', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        sendHealthWebhook($this, '/log');

        Telegraph::assertSent('📝 Log Health Data', false);
    });
});

describe('/yes command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendHealthWebhook($this, '/yes');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('shows error when no pending log exists', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        sendHealthWebhook($this, '/yes');

        Telegraph::assertSent('❌ No pending log to confirm.', false);
    });
});

describe('/no command', function (): void {
    it('replies not linked when no active link exists', function (): void {
        sendHealthWebhook($this, '/no');

        Telegraph::assertSent('🔒 Please link your account first.', false);
    });

    it('shows error when no pending log exists', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        sendHealthWebhook($this, '/no');

        Telegraph::assertSent('❌ No pending log to cancel.');
    });

    it('clears pending log and shows discarded message', function (): void {
        $user = User::factory()->create();
        $linkedChat = createLinkedChatForHealth($this, $user);

        $linkedChat->setPendingHealthLog([
            'log_type' => 'glucose',
            'glucose_value' => 140.0,
            'glucose_reading_type' => 'fasting',
            'is_health_data' => true,
        ]);

        sendHealthWebhook($this, '/no');

        Telegraph::assertSent('❌ Log discarded.', false);
        expect($linkedChat->fresh()->hasPendingHealthLog())->toBeFalse();
    });
});

describe('health data keywords detection', function (): void {
    it('detects glucose keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('My glucose is 140')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Glucose,
                glucoseValue: 140.0,
                glucoseReadingType: GlucoseReadingType::Random,
                glucoseUnit: GlucoseUnit::MgDl,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'My glucose is 140');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('detects insulin keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('Took 5 units of insulin')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Insulin,
                insulinUnits: 5.0,
                insulinType: InsulinType::Bolus,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'Took 5 units of insulin');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('detects carbs keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('Ate 45g carbs')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Food,
                carbsGrams: 45,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'Ate 45g carbs');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('detects exercise keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('Walked 30 minutes')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Exercise,
                exerciseType: 'walking',
                exerciseDurationMinutes: 30,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'Walked 30 minutes');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('detects weight keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('Weigh 180 lbs')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Vitals,
                weight: 81.65,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'Weigh 180 lbs');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('detects BP keyword in message', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->with('BP 120/80')
            ->andReturn(new HealthLogData(
                isHealthData: true,
                logType: HealthEntryType::Vitals,
                bpSystolic: 120,
                bpDiastolic: 80,
            ));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'BP 120/80');

        Telegraph::assertSent('📝 Log:', false);
    });

    it('shows confirmation instructions with yes/no', function (): void {
        $user = User::factory()->create();
        createLinkedChatForHealth($this, $user);

        $parserMock = Mockery::mock(ParsesHealthData::class);
        $parserMock->shouldReceive('parse')
            ->once()
            ->andReturn(new HealthLogData(isHealthData: true, logType: HealthEntryType::Glucose, glucoseValue: 140.0));
        app()->instance(ParsesHealthData::class, $parserMock);

        sendHealthWebhook($this, 'My glucose is 140');

        Telegraph::assertSent('/yes', false);
        Telegraph::assertSent('/no', false);
    });
});

describe('/help command includes health logging info', function (): void {
    it('shows health logging commands in help', function (): void {
        sendHealthWebhook($this, '/help');

        Telegraph::assertSent('/log', false);
    });
});

describe('regular chat message without health keywords', function (): void {
    it('does not trigger health log for regular messages', function (): void {
        $user = User::factory()->create();
        $linkedChat = createLinkedChatForHealth($this, $user);

        sendHealthWebhook($this, 'What should I eat for breakfast?');

        expect($linkedChat->fresh()->hasPendingHealthLog())->toBeFalse();
    });
});
