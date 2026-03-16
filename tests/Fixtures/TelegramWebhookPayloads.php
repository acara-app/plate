<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final class TelegramWebhookPayloads
{
    /**
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function photoMessage(string $chatId = '123456789', string $caption = '', array $overrides = []): array
    {
        $message = [
            'message_id' => 1,
            'from' => ['id' => 987654321, 'is_bot' => false, 'first_name' => 'Test'],
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'date' => now()->timestamp,
            'photo' => [
                ['file_id' => 'photo_small_id', 'file_unique_id' => 'small', 'width' => 90, 'height' => 90, 'file_size' => 1234],
                ['file_id' => 'photo_medium_id', 'file_unique_id' => 'medium', 'width' => 320, 'height' => 320, 'file_size' => 12345],
                ['file_id' => 'photo_large_id', 'file_unique_id' => 'large', 'width' => 800, 'height' => 800, 'file_size' => 45678],
            ],
        ];

        if ($caption !== '') {
            $message['caption'] = $caption;
        }

        return array_merge(['message' => $message], $overrides);
    }
}
