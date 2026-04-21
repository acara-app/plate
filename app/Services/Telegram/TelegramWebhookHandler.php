<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\DownloadsTelegramPhoto;
use App\Contracts\ProcessesAdvisorMessage;
use App\Exceptions\TelegramUserException;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;
use Laravel\Ai\Files\Base64Image;
use Throwable;

final class TelegramWebhookHandler extends WebhookHandler
{
    private const string PLATFORM = 'telegram';

    public function __construct(
        private readonly ProcessesAdvisorMessage $processAdvisorMessage,
        private readonly TelegramMessageService $telegramMessage,
        private readonly DownloadsTelegramPhoto $downloadTelegramPhoto,
    ) {}

    public function start(): void
    {
        $text = "👋 Welcome to Acara Plate!\n\n"
            ."I'm your AI wellness assistant. I can help you with:\n"
            ."• Log health data (glucose, food, insulin, weight, etc.)\n"
            ."• Nutrition advice and meal suggestions\n"
            ."• Meal plans and glucose spike predictions\n"
            ."• Fitness and wellness guidance\n\n"
            ."Commands:\n"
            ."/new - Start a new conversation\n"
            ."/reset - Clear conversation history\n"
            ."/me - Show your profile\n"
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
            ."/help - Show this help\n\n"
            ."Just tell me anything — I'll log health data, answer questions, or give advice!\n\n"
            ."Examples:\n"
            ."• 'My glucose is 140'\n"
            ."• 'Ate tsuivan for lunch'\n"
            ."• 'Walked 30 minutes'\n"
            ."• 'What should I eat for dinner?'";

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
        $user = $pendingChat?->user;

        if (! $pendingChat instanceof UserChatPlatformLink || ! $user instanceof User) {
            $this->chat->message('❌ Invalid or expired token.')->send();

            return;
        }

        $platformUserId = (string) $this->chat->chat_id;

        DB::transaction(function () use ($pendingChat, $user, $platformUserId): void {
            UserChatPlatformLink::query()
                ->where('platform', self::PLATFORM)
                ->where('id', '!=', $pendingChat->id)
                ->where(function ($query) use ($pendingChat, $platformUserId): void {
                    $query->where('user_id', $pendingChat->user_id)
                        ->orWhere('platform_user_id', $platformUserId);
                })
                ->delete();

            $pendingChat->update(['platform_user_id' => $platformUserId]);
            $pendingChat->markAsLinked($user);
        });

        $this->telegramMessage->sendLongMessage(
            $this->chat,
            "✅ Linked! Welcome, {$user->name}!\n\nTry asking:\n• What should I eat for breakfast?\n• Create a meal plan\n• My glucose is 140",
            false
        );
    }

    public function me(): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserChatPlatformLink) {
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

        if (! $linkedChat instanceof UserChatPlatformLink) {
            $this->replyNotLinked();

            return;
        }

        $conversationId = $this->processAdvisorMessage->resetConversation($linkedChat->user);
        $linkedChat->update(['conversation_id' => $conversationId]);

        $this->chat->message('✨ New conversation started! How can I help you?')->send();
    }

    public function reset(): void
    {
        $this->new();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserChatPlatformLink) {
            $this->replyNotLinked();

            return;
        }

        try {
            $this->telegramMessage->sendTypingIndicator($this->chat);

            $attachments = $this->extractPhotoAttachments();
            $message = $text->toString();

            if ($attachments !== [] && $message === '') {
                $message = 'Analyze this food photo and log it.';
            }

            $this->generateAndSendResponse($linkedChat, $message, $attachments);
        } catch (TelegramUserException $e) {
            $this->chat->message($e->getMessage())->send();
        } catch (Throwable $e) {
            report($e);
            $this->chat->message('❌ Error processing message. Please try again.')->send();
        }
    }

    /**
     * @return array<int, Base64Image>
     */
    private function extractPhotoAttachments(): array
    {
        if (! $this->message instanceof Message || $this->message->photos()->isEmpty()) {
            return [];
        }

        $photo = $this->message->photos()->last();

        return [$this->downloadTelegramPhoto->handle($this->bot, $photo)];
    }

    /**
     * @param  array<int, Base64Image>  $attachments
     */
    private function generateAndSendResponse(UserChatPlatformLink $linkedChat, string $message, array $attachments = []): void
    {
        $result = $this->processAdvisorMessage->handle(
            $linkedChat->user,
            $message,
            $linkedChat->conversation_id,
            $attachments,
        );

        if ($linkedChat->conversation_id === null) {
            $linkedChat->update(['conversation_id' => $result['conversation_id']]);
        }

        $this->telegramMessage->sendLongMessage($this->chat, $result['response'], true);
    }

    /**
     * @codeCoverageIgnore
     */
    private function formatProfileInfo(User $user): string
    {
        $profile = $user->profile;

        if ($profile === null) {
            return '';
        }

        $sex = $profile->sex !== null ? ucfirst($profile->sex->value) : 'N/A';
        $age = $profile->age !== null ? $profile->age.' years' : 'N/A';
        $height = $profile->height !== null ? $profile->height.'cm' : 'N/A';
        $weight = $profile->weight !== null ? $profile->weight.'kg' : 'N/A';

        $text = "\n\n📊 {$age}, {$sex}\n📏 {$height}, {$weight}";

        if ($profile->bmi !== null) {
            $text .= '
⚖️ BMI: '.$profile->bmi;
        }

        if ($profile->goal_choice !== null) {
            $text .= '
🎯 Goal: '.$profile->goal_choice->label();
        }

        if ($profile->calculated_diet_type !== null) {
            $text .= '
🥗 Diet: '.$profile->calculated_diet_type->shortName();
        }

        if ($profile->household_context !== null && $profile->household_context !== '') {
            $text .= "\n👨‍👩‍👧‍👦 Household: ".$profile->household_context;
        }

        return $text;
    }

    private function resolveLinkedChat(): ?UserChatPlatformLink
    {
        return UserChatPlatformLink::query()
            ->with('user')
            ->forUser(self::PLATFORM, (string) $this->chat->chat_id)
            ->linked()
            ->first();
    }

    private function findPendingChatByToken(string $token): ?UserChatPlatformLink
    {
        return UserChatPlatformLink::query()
            ->with('user')
            ->where('platform', self::PLATFORM)
            ->where('linking_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();
    }

    private function replyNotLinked(): void
    {
        $this->chat->message("🔒 Please link your account first.\n\n1. Go to Settings → Integrations\n2. Click Connect Telegram\n3. Use: /link YOUR_TOKEN")->send();
    }
}
