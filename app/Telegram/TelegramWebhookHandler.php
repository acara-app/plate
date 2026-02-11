<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Contracts\GeneratesAiResponse;
use App\Models\User;
use App\Models\UserTelegramChat;
use App\Services\Telegram\TelegramMessageService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Exception;
use Illuminate\Support\Stringable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly GeneratesAiResponse $generateAiResponse,
        private readonly TelegramMessageService $telegramMessage,
    ) {}

    public function start(): void
    {
        $text = "👋 Welcome to Acara Plate!\n\n"
            . "I'm your AI nutrition advisor. I can help you with:\n"
            . "• General nutrition advice\n"
            . "• Meal suggestions and meal plans\n"
            . "• Glucose spike predictions\n"
            . "• Dietary recommendations\n\n"
            . "Commands:\n"
            . "/new - Start a new conversation\n"
            . "/reset - Clear conversation history\n"
            . "/me - Show your profile\n"
            . "/help - Show all commands\n\n"
            . 'To get started, link your account in Settings → Integrations.';

        $this->telegramMessage->sendLongMessage($this->chat, $text, false);
    }

    public function help(): void
    {
        $text = "📚 Available Commands:\n\n"
            . "/start - Welcome message\n"
            . "/new - Start a new conversation\n"
            . "/reset - Clear conversation history\n"
            . "/me - Show your profile\n"
            . "/help - Show this help\n\n"
            . 'Just send me any message for nutrition advice!';

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
            "✅ Linked! Welcome, {$pendingChat->user->name}!\n\nTry asking:\n• What should I eat for breakfast?\n• Create a meal plan\n• Will this spike my glucose?",
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

    protected function handleChatMessage(Stringable $text): void
    {
        $linkedChat = $this->resolveLinkedChat();

        if (! $linkedChat instanceof UserTelegramChat) {
            $this->replyNotLinked();

            return;
        }

        $this->telegramMessage->sendTypingIndicator($this->chat);

        try {
            $this->generateAndSendResponse($linkedChat, $text->toString());
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Error processing message. Please try again.')->send();
        }
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
