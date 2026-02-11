<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final class TelegramWebhookPayloads
{
    /**
     * Build a Telegram webhook payload for a text message.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function message(string $text, string $chatId = '123456789', array $overrides = []): array
    {
        return array_merge([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 987654321, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'date' => now()->timestamp,
                'text' => $text,
            ],
        ], $overrides);
    }
}
