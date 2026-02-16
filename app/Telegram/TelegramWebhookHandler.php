<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Actions\SaveHealthLogAction;
use App\Contracts\GeneratesAiResponse;
use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use App\Enums\HealthEntryType;
use App\Exceptions\TelegramUserException;
use App\Models\User;
use App\Models\UserTelegramChat;
use App\Services\Telegram\TelegramMessageService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Stringable;
use Throwable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly GeneratesAiResponse $generateAiResponse,
        private readonly TelegramMessageService $telegramMessage,
        private readonly ParsesHealthData $healthDataParser,
        private readonly SaveHealthLogAction $saveHealthLog,
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
            $healthData = $this->reconstructHealthLogData($pendingLog);
            $this->saveHealthLog->handle($linkedChat->user, $healthData, $healthData->measuredAt);
            $linkedChat->clearPendingHealthLog();

            $this->chat->message('✅ Saved! Your health data has been logged.')->send();
        } catch (Throwable $e) {
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

            $this->telegramMessage->sendTypingIndicator($this->chat);
            $this->generateAndSendResponse($linkedChat, $message);
        } catch (TelegramUserException $e) {
            $this->chat->message($e->getMessage())->send();
        } catch (Throwable $e) {
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
        } catch (TelegramUserException $e) {
            $this->chat->message($e->getMessage())->send();
        } catch (Throwable $e) {
            report($e);
            $this->chat->message('❌ Could not understand that. Try something like: "My glucose is 140" or "Took 5 units insulin"')->send();
        }
    }

    private function handleHealthLogAttempt(UserTelegramChat $linkedChat, HealthLogData $healthData): void
    {
        $pendingLog = $this->serializeHealthLogData($healthData);
        $linkedChat->setPendingHealthLog($pendingLog);

        $formattedLog = $healthData->formatForDisplay();
        $confirmationText = "📝 Log: {$formattedLog}\n\nType /yes to confirm or /no to cancel.";

        $this->telegramMessage->sendLongMessage($this->chat, $confirmationText, false);
    }

    /**
     * Serialize HealthLogData to array for storage in pending log.
     *
     * @return array<string, mixed>
     */
    private function serializeHealthLogData(HealthLogData $data): array
    {
        return [
            'is_health_data' => $data->isHealthData,
            'log_type' => $data->logType->value,
            'glucose_value' => $data->glucoseValue,
            'glucose_reading_type' => $data->glucoseReadingType?->value,
            'glucose_unit' => $data->glucoseUnit?->value,
            'carbs_grams' => $data->carbsGrams,
            'insulin_units' => $data->insulinUnits,
            'insulin_type' => $data->insulinType?->value,
            'medication_name' => $data->medicationName,
            'medication_dosage' => $data->medicationDosage,
            'weight' => $data->weight,
            'blood_pressure_systolic' => $data->bpSystolic,
            'blood_pressure_diastolic' => $data->bpDiastolic,
            'exercise_type' => $data->exerciseType,
            'exercise_duration_minutes' => $data->exerciseDurationMinutes,
            'measured_at' => $data->measuredAt?->toISOString(),
        ];
    }

    /**
     * Reconstruct HealthLogData from stored pending log array.
     *
     * @param  array<string, mixed>  $log
     */
    private function reconstructHealthLogData(array $log): HealthLogData
    {
        /** @var string $logTypeString */
        $logTypeString = $log['log_type'] ?? 'glucose';
        $logType = HealthEntryType::tryFrom($logTypeString) ?? HealthEntryType::Glucose;

        /** @var string|null $glucoseReadingTypeString */
        $glucoseReadingTypeString = $log['glucose_reading_type'] ?? null;
        $glucoseReadingType = $glucoseReadingTypeString !== null
            ? \App\Enums\GlucoseReadingType::tryFrom($glucoseReadingTypeString)
            : null;

        /** @var string|null $glucoseUnitString */
        $glucoseUnitString = $log['glucose_unit'] ?? null;
        $glucoseUnit = $glucoseUnitString !== null
            ? \App\Enums\GlucoseUnit::tryFrom($glucoseUnitString)
            : null;

        /** @var string|null $insulinTypeString */
        $insulinTypeString = $log['insulin_type'] ?? null;
        $insulinType = $insulinTypeString !== null
            ? \App\Enums\InsulinType::tryFrom($insulinTypeString)
            : null;

        /** @var string|null $measuredAtString */
        $measuredAtString = $log['measured_at'] ?? null;
        $measuredAt = $measuredAtString !== null
            ? Date::parse($measuredAtString)
            : null;

        return new HealthLogData(
            isHealthData: (bool) ($log['is_health_data'] ?? false),
            logType: $logType,
            glucoseValue: $this->toFloatOrNull($log['glucose_value'] ?? null),
            glucoseReadingType: $glucoseReadingType,
            glucoseUnit: $glucoseUnit,
            carbsGrams: $this->toIntOrNull($log['carbs_grams'] ?? null),
            insulinUnits: $this->toFloatOrNull($log['insulin_units'] ?? null),
            insulinType: $insulinType,
            medicationName: $this->toStringOrNull($log['medication_name'] ?? null),
            medicationDosage: $this->toStringOrNull($log['medication_dosage'] ?? null),
            weight: $this->toFloatOrNull($log['weight'] ?? null),
            bpSystolic: $this->toIntOrNull($log['blood_pressure_systolic'] ?? null),
            bpDiastolic: $this->toIntOrNull($log['blood_pressure_diastolic'] ?? null),
            exerciseType: $this->toStringOrNull($log['exercise_type'] ?? null),
            exerciseDurationMinutes: $this->toIntOrNull($log['exercise_duration_minutes'] ?? null),
            measuredAt: $measuredAt,
        );
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function toIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    private function generateAndSendResponse(UserTelegramChat $linkedChat, string $message): void
    {
        $conversationId = $linkedChat->conversation_id;

        if ($conversationId === null) {
            $conversationId = $this->generateAiResponse->resetConversation($linkedChat->user);
            $linkedChat->update(['conversation_id' => $conversationId]);
        }

        $this->telegramMessage->sendTypingIndicator($this->chat);

        $streamResponse = $this->generateAiResponse->stream(
            $linkedChat->user,
            $message,
            $conversationId,
        );

        $chunks = [];
        foreach ($streamResponse as $event) {
            if ($event instanceof \Laravel\Ai\Streaming\Events\TextDelta) {
                $chunks[] = $event->delta;
            }
        }

        $this->telegramMessage->sendStreamingMessage($this->chat, $chunks, true);
        $this->telegramMessage->stopTypingIndicator($this->chat);
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
