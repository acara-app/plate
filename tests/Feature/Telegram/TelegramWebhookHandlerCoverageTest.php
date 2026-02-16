<?php

declare(strict_types=1);

use App\Contracts\ParsesHealthData;
use App\Contracts\ProcessesAdvisorMessage;
use App\Contracts\SavesHealthLog;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use App\Exceptions\TelegramUserException;
use App\Models\User;
use App\Models\UserTelegramChat;
use Carbon\CarbonInterface;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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


describe('TelegramWebhookHandler coverage', function (): void {
    $sendWebhook = function (mixed $test, string $text): Illuminate\Testing\TestResponse {
        return $test->postJson(
            route('telegraph.webhook', ['token' => $test->bot->token]),
            TelegramWebhookPayloads::message($text, (string) $test->telegraphChat->chat_id),
        );
    };

    it('catches TelegramUserException during pending log state', function () use ($sendWebhook): void {
        $user = User::factory()->create();
        UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => ['is_health_data' => true], // Simulate pending state
        ]);

        $parserMock = new class implements ParsesHealthData
        {
            public function parse(string $message): HealthLogData
            {
                throw new TelegramUserException('Custom user error');
            }
        };

        app()->instance(ParsesHealthData::class, $parserMock);

        $sendWebhook($this, 'Invalid input');

        Telegraph::assertSent('Custom user error');
    });

    it('reconstructs HealthLogData with all enums and dates', function () use ($sendWebhook): void {
        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'glucose_value' => 120,
                'glucose_reading_type' => GlucoseReadingType::PostMeal->value,
                'glucose_unit' => 'mg/dL', // checking string to enum
                'insulin_type' => InsulinType::Bolus->value,
                'measured_at' => '2023-10-27T10:00:00.000000Z',
                'medication_name' => 123, // scalar to string check
            ],
        ]);

        // Create a simple test implementation that captures calls
        $saveActionMock = new class implements SavesHealthLog
        {
            public array $calls = [];

            public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void
            {
                $this->calls[] = [
                    'data' => $data,
                ];
            }
        };

        app()->instance(SavesHealthLog::class, $saveActionMock);

        $sendWebhook($this, 'yes');

        expect($saveActionMock->calls)->toHaveCount(1);
        $data = $saveActionMock->calls[0]['data'];

        expect($data->glucoseReadingType)->toBe(GlucoseReadingType::PostMeal);
        expect($data->insulinType)->toBe(InsulinType::Bolus);
        expect($data->measuredAt)->not->toBeNull();
        expect($data->measuredAt->toIsoString())->toContain('2023-10-27T10:00:00');
        expect($data->medicationName)->toBe('123'); // Verified scalar to string conversion
    });

    it('handles scalar values in toStringOrNull', function () use ($sendWebhook): void {


        $user = User::factory()->create();
        $chat = UserTelegramChat::factory()->for($user)->linked()->create([
            'telegraph_chat_id' => $this->telegraphChat->id,
            'pending_health_log' => [
                'is_health_data' => true,
                'log_type' => 'glucose',
                'medication_name' => true, // boolean is scalar
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

        $sendWebhook($this, 'yes');

        expect($saveActionMock->calls[0]['data']->medicationName)->toBe('1');
    });
});
