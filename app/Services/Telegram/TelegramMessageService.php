<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use DefStudio\Telegraph\Models\TelegraphChat;
use Exception;
use Illuminate\Support\Facades\Log;

final class TelegramMessageService
{
    private const int MAX_MESSAGE_LENGTH = 4096;

    private const int CHUNK_DELAY_MS = 1000;

    private const string QUEUE_NAME = 'telegram';

    public static function getMaxMessageLength(): int
    {
        return self::MAX_MESSAGE_LENGTH;
    }

    public function sendLongMessage(TelegraphChat $chat, string $message, bool $markdown = true): void
    {
        $chunks = $this->splitMessage($message);

        foreach ($chunks as $index => $chunk) {
            $this->dispatchMessage($chat, $chunk, $markdown);

            if ($index < count($chunks) - 1) {
                usleep(self::CHUNK_DELAY_MS * 1000);
            }
        }
    }

    public function splitMessage(string $message): array
    {
        $message = mb_trim($message);

        if (mb_strlen($message) <= self::MAX_MESSAGE_LENGTH) {
            return [$message];
        }

        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= self::MAX_MESSAGE_LENGTH) {
                $chunks[] = mb_trim($remaining);
                break;
            }

            $chunk = $this->extractChunk($remaining);
            $chunks[] = mb_trim($chunk);
            $remaining = mb_trim(mb_substr($remaining, mb_strlen($chunk)));
        }

        return array_filter($chunks, fn (string $c): bool => $c !== '');
    }

    public function sendTypingIndicator(TelegraphChat $chat): void
    {
        try {
            $chat->action('typing')->dispatch(self::QUEUE_NAME);
        } catch (Exception $e) {
            Log::warning('Failed to send typing action', ['error' => $e->getMessage()]);
        }
    }

    private function extractChunk(string $text): string
    {
        $maxLen = self::MAX_MESSAGE_LENGTH;
        $searchText = mb_substr($text, 0, $maxLen);
        $lastParagraph = mb_strrpos($searchText, "\n\n");

        if ($lastParagraph !== false && $lastParagraph > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastParagraph);
        }

        $lastNewline = mb_strrpos($searchText, "\n");

        if ($lastNewline !== false && $lastNewline > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastNewline);
        }

        $lastSentence = $this->findLastSentenceEnd($searchText);

        if ($lastSentence !== false && $lastSentence > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastSentence + 1);
        }

        $lastSpace = mb_strrpos($searchText, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastSpace);
        }

        return $searchText;
    }

    private function findLastSentenceEnd(string $text): int|false
    {
        $lastPos = false;

        foreach (['. ', '! ', '? ', ".\n", "!\n", "?\n"] as $ending) {
            $pos = mb_strrpos($text, $ending);

            if ($pos !== false && ($lastPos === false || $pos > $lastPos)) {
                $lastPos = $pos;
            }
        }

        return $lastPos;
    }

    private function dispatchMessage(TelegraphChat $chat, string $chunk, bool $markdown): void
    {
        $message = $chat->message($chunk);

        if ($markdown) {
            $message = $message->markdown();
        }

        $message->dispatch(self::QUEUE_NAME);
    }
}
