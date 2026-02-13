<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Actions\RecordDiabetesLogAction;
use App\Contracts\GeneratesAiResponse;
use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\InsulinType;
use App\Models\User;
use App\Models\UserTelegramChat;
use App\Services\Telegram\TelegramMessageService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Stringable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly GeneratesAiResponse $generateAiResponse,
        private readonly TelegramMessageService $telegramMessage,
        private readonly ParsesHealthData $healthDataParser,
        private readonly RecordDiabetesLogAction $recordDiabetesLog,
    ) {}

    public function start(): void
    {
        $text = "👋 Welcome to Acara Plate!\n\n"
            ."I'm your AI nutrition advisor. I can help you with:\n"
            ."• General nutrition advice\n"
            ."• Meal suggestions and meal plans\n"
            ."• Glucose spike predictions\n"
            ."• Log health data (glucose, insulin, weight, etc.)\n"
            ."• Dietary recommendations\n\n"
            ."Commands:\n"
            ."/new - Start a new conversation\n"
            ."/reset - Clear conversation history\n"
            ."/me - Show your profile\n"
            ."/log - Log health data (glucose, insulin, etc.)\n"
            ."/help - Show all commands\n\n"
            .'To get started, link your account in Settings → Integrations.';

        $this->telegramMessage->sendLongMessage($this->chat, $text, false);
    }

    public function help(): void
    {
        $text = "📚 Available Commands:\n\n"
            ."/start - Welcome message\n"
            ."/new - Start a new conversation\n"
            ."/reset - Clear conversation history\n"
            ."/me - Show your profile\n"
            ."/log - Log health data (glucose, insulin, weight, etc.)\n"
            ."/help - Show this help\n\n"
            ."You can also log health data by just describing it, like:\n"
            ."• 'My glucose is 140'\n"
            ."• 'Took 5 units of insulin'\n"
            ."• 'Walked 30 minutes'\n\n"
            .'Just send me any message for nutrition advice!';

        $this->telegramMessage->sendLongMessage($this->chat, $text, false);
    }

    public function link(string $token): void
    {
        $token = mb_strtoupper(mb_trim($token));

        if (mb_strlen($token) !== 8) {
            $this->chat->message('❌ Invalid token. Use: /link ABC123XY')->send();

            return;
        }

        $pendingChat = $this->findPendingChatByToken($token);

        if (! $pendingChat instanceof UserTelegramChat) {
            $this->chat->message('❌ Invalid or expired token.')->send();

            return;
        }

        $this->deactivateExistingLinks();
        $this->removeOtherChatsForUser($pendingChat);

        $pendingChat->update(['telegraph_chat_id' => $this->chat->id]);
        $pendingChat->markAsLinked();

        $this->telegramMessage->sendLongMessage(
            $this->chat,
            "✅ Linked! Welcome, {$pendingChat->user->name}!\n\nTry asking:\n• What should I eat for breakfast?\n• Create a meal plan\n• Log my glucose 140",
            false
        );
    }

    public function me(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $user = $linkedChat->user;
        $text = "👤 {$user->name}\n📧 {$user->email}";
        $text .= $this->formatProfileInfo($user);

        $this->telegramMessage->sendLongMessage($this->chat, $text, false);
    }

    public function new(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $conversationId = $this->generateAiResponse->resetConversation($linkedChat->user);
        $linkedChat->update(['conversation_id' => $conversationId]);

        $this->chat->message('✨ New conversation started! How can I help you?')->send();
    }

    public function reset(): void
    {
        $this->new();
    }

    public function log(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $text = "📝 Log Health Data\n\n"
            ."Just tell me what you want to log, for example:\n"
            ."• 'My glucose is 140'\n"
            ."• 'Took 5 units of insulin'\n"
            ."• 'Ate 45g carbs'\n"
            ."• 'Walked 30 minutes'\n"
            ."• 'Weight 180 lbs'\n"
            ."• 'BP 120/80'";

        $this->telegramMessage->sendLongMessage($this->chat, $text, false);
    }

    public function yes(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $pendingLog = $linkedChat->getPendingHealthLog();

        if ($pendingLog === null) {
            $this->chat->message('❌ No pending log to confirm. Just tell me what you want to log!')->send();

            return;
        }

        try {
            $this->saveHealthLog($linkedChat, $pendingLog);
            $linkedChat->clearPendingHealthLog();

            $this->chat->message('✅ Saved! Your health data has been logged.')->send();
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Error saving log. Please try again.')->send();
        }
    }

    public function no(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        if (! $linkedChat->hasPendingHealthLog()) {
            $this->chat->message('❌ No pending log to cancel.')->send();

            return;
        }

        $linkedChat->clearPendingHealthLog();
        $this->chat->message('❌ Log discarded. Tell me if you want to log something else!')->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $message = $text->toString();

        if ($linkedChat->hasPendingHealthLog()) {
            $this->handlePendingLogState($linkedChat, $message);

            return;
        }

        try {
            $healthData = $this->healthDataParser->parse($message);

            if ($healthData->isHealthData) {
                $this->handleHealthLogAttempt($linkedChat, $healthData);

                return;
            }
        } catch (Exception $e) {
            report($e);
        }

        $this->telegramMessage->sendTypingIndicator($this->chat);

        try {
            $this->generateAndSendResponse($linkedChat, $message);
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Error processing message. Please try again.')->send();
        }
    }

    private function handlePendingLogState(UserTelegramChat $linkedChat, string $message): void
    {
        $normalizedMessage = mb_strtolower(mb_trim($message));

        if ($normalizedMessage === 'yes' || $normalizedMessage === '/yes') {
            $this->yes();

            return;
        }

        if ($normalizedMessage === 'no' || $normalizedMessage === '/no') {
            $this->no();

            return;
        }

        try {
            $healthData = $this->healthDataParser->parse($message);
            $this->handleHealthLogAttempt($linkedChat, $healthData);
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Could not understand that. Try something like: "My glucose is 140" or "Took 5 units insulin"')->send();
        }
    }

    private function handleHealthLogAttempt(UserTelegramChat $linkedChat, HealthLogData $healthData): void
    {
        try {
            $pendingLog = [
                'log_type' => $healthData->logType,
                'glucose_value' => $healthData->glucoseValue,
                'glucose_reading_type' => $healthData->glucoseReadingType,
                'glucose_unit' => $healthData->glucoseUnit,
                'carbs_grams' => $healthData->carbsGrams,
                'insulin_units' => $healthData->insulinUnits,
                'insulin_type' => $healthData->insulinType,
                'medication_name' => $healthData->medicationName,
                'medication_dosage' => $healthData->medicationDosage,
                'weight' => $healthData->weight,
                'blood_pressure_systolic' => $healthData->bpSystolic,
                'blood_pressure_diastolic' => $healthData->bpDiastolic,
                'exercise_type' => $healthData->exerciseType,
                'exercise_duration_minutes' => $healthData->exerciseDurationMinutes,
                'measured_at' => $healthData->measuredAt?->toISOString(),
            ];

            $linkedChat->setPendingHealthLog($pendingLog);

            $formattedLog = $this->formatPendingLog($pendingLog);
            $confirmationText = "📝 Log: {$formattedLog}\n\nType /yes to confirm or /no to cancel.";

            $this->telegramMessage->sendLongMessage($this->chat, $confirmationText, false);
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Could not understand that. Try something like: "My glucose is 140" or "Took 5 units insulin"')->send();
        }
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatPendingLog(array $log): string
    {
        /** @var string $logType */
        $logType = $log['log_type'];

        return match ($logType) {
            'glucose' => $this->formatGlucoseLog($log),
            'food' => $this->formatFoodLog($log),
            'insulin' => $this->formatInsulinLog($log),
            'meds' => $this->formatMedsLog($log),
            'vitals' => $this->formatVitalsLog($log),
            'exercise' => $this->formatExerciseLog($log),
            default => 'Unknown data',
        };
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatGlucoseLog(array $log): string
    {
        /** @var float|null $value */
        $value = $log['glucose_value'];
        /** @var string|null $unit */
        $unit = $log['glucose_unit'] ?? 'mg/dL';
        /** @var string|null $readingType */
        $readingType = $log['glucose_reading_type'] ?? 'random';

        $readingTypeLabel = match ($readingType) {
            'fasting' => 'Fasting',
            'before-meal' => 'Before meal',
            'post-meal' => 'Post-meal',
            default => 'Random',
        };

        return "Glucose {$value} {$unit} ({$readingTypeLabel})";
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatFoodLog(array $log): string
    {
        /** @var int|null $carbs */
        $carbs = $log['carbs_grams'];

        return "Food - {$carbs}g carbs";
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatInsulinLog(array $log): string
    {
        /** @var float|null $units */
        $units = $log['insulin_units'];
        /** @var string|null $type */
        $type = $log['insulin_type'] ?? 'bolus';

        $typeLabel = match ($type) {
            'basal' => 'Basal',
            'bolus' => 'Bolus',
            'mixed' => 'Mixed',
            default => $type,
        };

        return "Insulin {$units} units ({$typeLabel})";
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatMedsLog(array $log): string
    {
        /** @var string|null $name */
        $name = $log['medication_name'];
        /** @var string|null $dosage */
        $dosage = $log['medication_dosage'] ?? '';

        return "Medication - {$name}".($dosage ? " {$dosage}" : '');
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatVitalsLog(array $log): string
    {
        /** @var float|null $weight */
        $weight = $log['weight'];
        /** @var int|null $bpSystolic */
        $bpSystolic = $log['blood_pressure_systolic'];
        /** @var int|null $bpDiastolic */
        $bpDiastolic = $log['blood_pressure_diastolic'];

        if ($weight !== null) {
            return "Weight {$weight} kg";
        }

        if ($bpSystolic !== null && $bpDiastolic !== null) {
            return "Blood Pressure {$bpSystolic}/{$bpDiastolic}";
        }

        return 'Vitals';
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function formatExerciseLog(array $log): string
    {
        /** @var string|null $type */
        $type = $log['exercise_type'] ?? 'exercise';
        /** @var int|null $duration */
        $duration = $log['exercise_duration_minutes'];

        return "Exercise - {$duration} min {$type}";
    }

    /**
     * @param  array<string, mixed>  $logData
     */
    private function saveHealthLog(UserTelegramChat $linkedChat, array $logData): void
    {
        $user = $linkedChat->user;
        $profile = $user->profile;
        $glucoseUnit = $profile !== null ? $profile->units_preference : null;
        $glucoseUnit = $glucoseUnit ?? GlucoseUnit::MmolL;

        /** @var string $logType */
        $logType = $logData['log_type'];
        /** @var string|null $measuredAtString */
        $measuredAtString = $logData['measured_at'] ?? null;
        $measuredAt = $measuredAtString !== null
            ? Carbon::parse($measuredAtString)
            : now();

        $recordData = [
            'user_id' => $user->id,
            'measured_at' => $measuredAt,
            'notes' => null,
        ];

        match ($logType) {
            'glucose' => $this->saveGlucoseLog($logData, $recordData, $glucoseUnit),
            'food' => $this->saveFoodLog($logData, $recordData),
            'insulin' => $this->saveInsulinLog($logData, $recordData),
            'meds' => $this->saveMedsLog($logData, $recordData),
            'vitals' => $this->saveVitalsLog($logData, $recordData),
            'exercise' => $this->saveExerciseLog($logData, $recordData),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveGlucoseLog(array $logData, array $recordData, GlucoseUnit $glucoseUnit): void
    {
        /** @var float|null $glucoseValue */
        $glucoseValue = $logData['glucose_value'];

        if ($glucoseValue !== null && $glucoseUnit === GlucoseUnit::MmolL) {
            $glucoseValue = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        $recordData['glucose_value'] = $glucoseValue;

        /** @var string|null $glucoseReadingType */
        $glucoseReadingType = $logData['glucose_reading_type'];
        $recordData['glucose_reading_type'] = $glucoseReadingType !== null
            ? GlucoseReadingType::tryFrom($glucoseReadingType)
            : null;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveFoodLog(array $logData, array $recordData): void
    {
        /** @var int|null $carbsGrams */
        $carbsGrams = $logData['carbs_grams'];
        $recordData['carbs_grams'] = $carbsGrams;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveInsulinLog(array $logData, array $recordData): void
    {
        /** @var float|null $insulinUnits */
        $insulinUnits = $logData['insulin_units'];
        $recordData['insulin_units'] = $insulinUnits;

        /** @var string|null $insulinType */
        $insulinType = $logData['insulin_type'];
        $recordData['insulin_type'] = $insulinType !== null
            ? InsulinType::tryFrom($insulinType)
            : null;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveMedsLog(array $logData, array $recordData): void
    {
        /** @var string|null $medicationName */
        $medicationName = $logData['medication_name'];
        $recordData['medication_name'] = $medicationName;

        /** @var string|null $medicationDosage */
        $medicationDosage = $logData['medication_dosage'];
        $recordData['medication_dosage'] = $medicationDosage;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveVitalsLog(array $logData, array $recordData): void
    {
        /** @var float|null $weight */
        $weight = $logData['weight'];
        $recordData['weight'] = $weight;

        /** @var int|null $bpSystolic */
        $bpSystolic = $logData['blood_pressure_systolic'];
        $recordData['blood_pressure_systolic'] = $bpSystolic;

        /** @var int|null $bpDiastolic */
        $bpDiastolic = $logData['blood_pressure_diastolic'];
        $recordData['blood_pressure_diastolic'] = $bpDiastolic;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    /**
     * @param  array<string, mixed>  $logData
     * @param  array<string, mixed>  $recordData
     */
    private function saveExerciseLog(array $logData, array $recordData): void
    {
        /** @var string|null $exerciseType */
        $exerciseType = $logData['exercise_type'];
        $recordData['exercise_type'] = $exerciseType;

        /** @var int|null $exerciseDuration */
        $exerciseDuration = $logData['exercise_duration_minutes'];
        $recordData['exercise_duration_minutes'] = $exerciseDuration;

        /** @var array<string, mixed> $data */
        $data = $recordData;
        $this->recordDiabetesLog->handle($data);
    }

    private function generateAndSendResponse(UserTelegramChat $linkedChat, string $message): void
    {
        $result = $this->generateAiResponse->handle(
            $linkedChat->user,
            $message,
            $linkedChat->conversation_id,
        );

        if ($linkedChat->conversation_id === null) {
            $linkedChat->update(['conversation_id' => $result['conversation_id']]);
        }

        $this->telegramMessage->sendLongMessage($this->chat, $result['response']);
    }

    private function formatProfileInfo(User $user): string
    {
        $profile = $user->profile;

        if ($profile === null) {
            return '';
        }

        $sex = $profile->sex !== null ? ucfirst($profile->sex->value) : 'N/A';
        $age = $profile->age !== null ? "{$profile->age} years" : 'N/A';
        $height = $profile->height !== null ? "{$profile->height}cm" : 'N/A';
        $weight = $profile->weight !== null ? "{$profile->weight}kg" : 'N/A';

        return "\n\n📊 {$age}, {$sex}\n📏 {$height}, {$weight}";
    }

    private function resolveLinkedChat(): ?UserTelegramChat
    {
        return UserTelegramChat::query()
            ->with('user')
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->whereNotNull('linked_at')
            ->first();
    }

    private function findPendingChatByToken(string $token): ?UserTelegramChat
    {
        return UserTelegramChat::query()
            ->where('linking_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();
    }

    private function deactivateExistingLinks(): void
    {
        UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function removeOtherChatsForUser(UserTelegramChat $pendingChat): void
    {
        UserTelegramChat::query()
            ->where('user_id', $pendingChat->user_id)
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('id', '!=', $pendingChat->id)
            ->delete();
    }

    private function replyNotLinked(): void
    {
        $this->chat->message("🔒 Please link your account first.\n\n1. Go to Settings → Integrations\n2. Click Connect Telegram\n3. Use: /link YOUR_TOKEN")->send();
    }
}
