<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Actions\GenerateAiResponseAction;
use App\Enums\AgentMode;
use App\Models\User;
use App\Models\UserTelegramChat;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Exception;
use Illuminate\Support\Stringable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(private readonly GenerateAiResponseAction $generateAiResponse) {}

    /**
     * Handle the /start command.
     */
    public function start(): void
    {
        $welcomeText = "👋 Welcome to Acara Plate!\n\n"
            ."I'm your AI nutrition advisor. I can help you with:\n"
            ."• General nutrition advice\n"
            ."• Meal suggestions and meal plans\n"
            ."• Glucose spike predictions\n"
            ."• Dietary recommendations\n\n"
            ."To get started, you need to link your account:\n\n"
            ."1. Visit your account settings\n"
            ."2. Go to \"Integrations\"\n"
            ."3. Click \"Connect Telegram\"\n"
            ."4. Use the /link command with your token\n\n"
            .'Or type /help to see all available commands.';

        $this->chat->message($welcomeText)->send();
    }

    /**
     * Handle the /help command.
     */
    public function help(): void
    {
        $helpText = "📚 Available Commands:\n\n"
            ."/start - Welcome message and setup instructions\n"
            ."/link <token> - Link your Telegram to your account\n"
            ."/me - Show your profile information\n"
            ."/help - Show this help message\n\n"
            ."💬 Just send me any message and I'll help you with nutrition advice!\n\n"
            ."Examples:\n"
            ."• \"What should I eat for breakfast?\"\n"
            ."• \"I'm at Chipotle, what should I order?\"\n"
            ."• \"Create a 7-day meal plan for me\"\n"
            .'• "Will pizza spike my glucose?"';

        $this->chat->message($helpText)->send();
    }

    /**
     * Handle the /link command.
     */
    public function link(string $token): void
    {
        $token = mb_strtoupper(mb_trim($token));

        if (mb_strlen($token) !== 8) {
            $this->chat->message('❌ Invalid token format. Please use: /link ABC123XY')->send();

            return;
        }

        // Find the user telegram chat by token
        $userTelegramChat = UserTelegramChat::query()
            ->where('linking_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();

        if (! $userTelegramChat instanceof UserTelegramChat) {
            $this->chat->message('❌ Invalid or expired token. Please generate a new token from your account settings.')->send();

            return;
        }

        // Check if this chat is already linked to a different user
        $existingLink = UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->first();

        if ($existingLink instanceof UserTelegramChat && $existingLink->id !== $userTelegramChat->id) {
            // Deactivate the old link
            $existingLink->update(['is_active' => false]);
        }

        // Update the chat ID and mark as linked
        $userTelegramChat->update([
            'telegraph_chat_id' => $this->chat->id,
        ]);
        $userTelegramChat->markAsLinked();

        $user = $userTelegramChat->user;

        $successText = "✅ Successfully linked!\n\n"
            ."Welcome, {$user->name}!\n"
            ."Your Telegram is now connected to your Acara Plate account.\n\n"
            ."You can now chat with me directly here. Try asking:\n"
            ."• \"What should I eat for breakfast?\"\n"
            ."• \"Create a meal plan for me\"\n"
            ."• \"Will this food spike my glucose?\"\n\n"
            .'Type /help for more options.';

        $this->chat->message($successText)->send();
    }

    /**
     * Handle the /me command.
     */
    public function me(): void
    {
        $user = $this->getLinkedUser();

        if (! $user instanceof User) {
            $this->replyNotLinked();

            return;
        }

        $profile = $user->profile;

        $profileText = "👤 Your Profile:\n\n"
            ."Name: {$user->name}\n"
            ."Email: {$user->email}";

        if ($profile instanceof \App\Models\UserProfile) {
            $profileText .= "\n\nBiometrics:\n"
                ."Age: {$profile->age}\n"
                ."Gender: {$profile->gender}\n"
                ."Height: {$profile->height_cm} cm\n"
                ."Weight: {$profile->weight_kg} kg";
        }

        $profileText .= "\n\nType /help for available commands.";

        $this->chat->message($profileText)->send();
    }

    /**
     * Handle regular text messages.
     */
    protected function handleChatMessage(Stringable $text): void
    {
        $user = $this->getLinkedUser();

        if (! $user instanceof User) {
            $this->replyNotLinked();

            return;
        }

        // Show typing indicator
        $this->chat->action('typing')->send();

        try {
            // Determine mode based on message content
            $mode = $this->detectMode($text->toString());

            // Generate AI response
            $response = $this->generateAiResponse->handle($user, $text->toString(), $mode);

            // Send response
            $this->chat->message($response)->send();
        } catch (Exception $e) {
            report($e);
            $this->chat->message('❌ Sorry, I encountered an error processing your message. Please try again later.')->send();
        }
    }

    /**
     * Get the linked user for this chat.
     */
    private function getLinkedUser(): ?User
    {
        $link = UserTelegramChat::query()
            ->where('telegraph_chat_id', $this->chat->id)
            ->where('is_active', true)
            ->whereNotNull('linked_at')
            ->first();

        if (! $link instanceof UserTelegramChat) {
            return null;
        }

        return $link->user;
    }

    /**
     * Reply when user is not linked.
     */
    private function replyNotLinked(): void
    {
        $notLinkedText = "🔒 Account Not Linked\n\n"
            ."Please link your Telegram account first.\n\n"
            ."1. Visit your account settings on Acara Plate\n"
            ."2. Go to \"Integrations\"\n"
            ."3. Click \"Connect Telegram\"\n"
            ."4. Use the /link command with your token\n\n"
            .'Example: /link ABC123XY';

        $this->chat->message($notLinkedText)->send();
    }

    /**
     * Detect the agent mode based on message content.
     */
    private function detectMode(string $message): AgentMode
    {
        $lower = mb_strtolower($message);

        if (str_contains($lower, 'meal plan') || str_contains($lower, 'weekly plan') || str_contains($lower, 'daily meals')) {
            return AgentMode::GenerateMealPlan;
        }

        return AgentMode::Ask;
    }
}
