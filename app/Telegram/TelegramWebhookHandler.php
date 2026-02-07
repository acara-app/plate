<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Actions\GenerateAiResponseAction;
use App\Models\User;
use App\Models\UserTelegramChat;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Exception;
use Illuminate\Support\Stringable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(private readonly GenerateAiResponseAction $generateAiResponse) {}

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

        $this->chat->message($text)->send();
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

        $this->chat->message($text)->send();
    }

    public function link(string $token): void
    {
        $token = mb_strtoupper(mb_trim($token));

        if (mb_strlen($token) !== 8) {
            $this->chat->message('❌ Invalid token. Use: /link ABC123XY')->send();

            return;
        }

        $chat = UserTelegramChat::query()
            ->where('linking_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();

        if (! $chat instanceof UserTelegramChat) {
            $this->chat->message('❌ Invalid or expired token.')->send();

            return;
        }

        UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $chat->update(['telegraph_chat_id' => $this->chat->id]);
        $chat->markAsLinked();

        $this->chat->message("✅ Linked! Welcome, {$chat->user->name}!\n\nTry asking:\n• What should I eat for breakfast?\n• Create a meal plan\n• Will this spike my glucose?")->send();
    }

    public function me(): void
    {
        $user = $this->getLinkedUser();

        if (! $user instanceof User) {
            $this->replyNotLinked();

            return;
        }

        $text = "👤 {$user->name}\n📧 {$user->email}";

        if ($user->profile) {
            $text .= "\n\n📊 {$user->profile->age} years, {$user->profile->gender}\n"
                . "📏 {$user->profile->height_cm}cm, {$user->profile->weight_kg}kg";
        }

        $this->chat->message($text)->send();
    }

    public function new(): void
    {
        $user = $this->getLinkedUser();

        if (! $user instanceof User) {
            $this->replyNotLinked();

            return;
        }

        $chat = $this->getUserTelegramChat();
        $conversationId = $this->generateAiResponse->resetConversation($user);

        if ($chat instanceof UserTelegramChat) {
            $chat->update(['conversation_id' => $conversationId]);
        }

        $this->chat->message('✨ New conversation started! How can I help you?')->send();
    }

    public function reset(): void
    {
        $this->new();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $user = $this->getLinkedUser();

        if (! $user instanceof User) {
            $this->replyNotLinked();

            return;
        }

        $chat = $this->getUserTelegramChat();
        $conversationId = $chat?->conversation_id;

        $this->chat->action('typing')->send();

        try {
            $result = $this->generateAiResponse->handle($user, $text->toString(), $conversationId);

            if ($chat instanceof UserTelegramChat && $conversationId === null) {
                $chat->update(['conversation_id' => $result['conversation_id']]);
            }

            $this->chat->markdown($result['response'])->send();
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Error processing message. Please try again.')->send();
        }
    }

    private function getLinkedUser(): ?User
    {
        $link = UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->whereNotNull('linked_at')
            ->first();

        return $link?->user;
    }

    private function getUserTelegramChat(): ?UserTelegramChat
    {
        return UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->whereNotNull('linked_at')
            ->first();
    }

    private function replyNotLinked(): void
    {
        $this->chat->message("🔒 Please link your account first.\n\n1. Go to Settings → Integrations\n2. Click Connect Telegram\n3. Use: /link YOUR_TOKEN")->send();
    }
}
