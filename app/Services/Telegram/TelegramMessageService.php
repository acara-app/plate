<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Sleep;
use League\CommonMark\CommonMarkConverter;

final class TelegramMessageService
{
    public const int MAX_MESSAGE_LENGTH = 4096;

    private const int CHUNK_DELAY_MS = 500;

    private const string QUEUE_NAME = 'telegram';

    private const float MIN_SPLIT_THRESHOLD = 0.3;

    private const array SENTENCE_ENDINGS = ['. ', '! ', '? ', ".\n", "!\n", "?\n"];

    private const array SPLIT_PRIORITIES = ["\n\n", "\n"];

    private CommonMarkConverter $markdownConverter;

    public function __construct()
    {
        $this->markdownConverter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public static function getMaxMessageLength(): int
    {
        return self::MAX_MESSAGE_LENGTH;
    }

    public function sendLongMessage(TelegraphChat $chat, string $message, bool $markdown = true): void
    {
        $chunks = $this->splitMessage($message);

        foreach ($chunks as $index => $chunk) {
            $content = $markdown ? $this->convertToHtml($chunk) : $chunk;
            $this->dispatchMessage($chat, $content);

            if ($index < count($chunks) - 1) {
                Sleep::usleep(self::CHUNK_DELAY_MS * 1000);
            }
        }
    }

    public function sendStreamingMessage(TelegraphChat $chat, iterable $chunks, bool $html = false): void
    {
        $buffer = '';
        $chunkCount = 0;

        foreach ($chunks as $chunk) {
            $buffer .= $chunk;
            $chunkCount++;

            if (mb_strlen($buffer) >= 500 || $chunkCount >= 10) {
                $content = $html ? $buffer : $this->convertToHtml($buffer);
                $this->dispatchMessage($chat, $content);
                Sleep::usleep(self::CHUNK_DELAY_MS * 1000);
                $buffer = '';
                $chunkCount = 0;
            }
        }

        if ($buffer !== '') {
            $content = $html ? $buffer : $this->convertToHtml($buffer);
            $this->dispatchMessage($chat, $content);
        }
    }

    public function convertToHtml(string $markdown): string
    {
        return $this->markdownConverter->convert($markdown)->getContent();
    }

    /**
     * @return array<string>
     */
    public function splitMessage(string $message): array
    {
        $message = mb_trim($message);

        if (mb_strlen($message) <= self::MAX_MESSAGE_LENGTH) {
            return [$message];
        }

        return $this->chunkMessage($message);
    }

    public function sendTypingIndicator(TelegraphChat $chat): void
    {
        $chat->action('typing')->send();
    }

    public function stopTypingIndicator(TelegraphChat $chat): void
    {
        $chat->action('typing')->send();
    }

    /**
     * @return array<string>
     */
    private function chunkMessage(string $message): array
    {
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

        return array_values(array_filter($chunks, fn (string $chunk): bool => $chunk !== ''));
    }

    private function extractChunk(string $text): string
    {
        $maxLength = self::MAX_MESSAGE_LENGTH;
        $searchText = mb_substr($text, 0, $maxLength);
        $threshold = (int) ($maxLength * self::MIN_SPLIT_THRESHOLD);

        foreach (self::SPLIT_PRIORITIES as $delimiter) {
            if ($chunk = $this->findSplitPoint($searchText, $delimiter, $threshold)) {
                return $chunk;
            }
        }

        if ($chunk = $this->findSentenceEndSplit($searchText, $threshold)) {
            return $chunk;
        }

        if ($chunk = $this->findSplitPoint($searchText, ' ', $threshold)) {
            return $chunk;
        }

        return $searchText;
    }

    private function findSplitPoint(string $text, string $delimiter, int $threshold): ?string
    {
        $position = mb_strrpos($text, $delimiter);

        if ($position === false || $position <= $threshold) {
            return null;
        }

        return mb_substr($text, 0, $position);
    }

    private function findSentenceEndSplit(string $text, int $threshold): ?string
    {
        $lastPosition = null;

        foreach (self::SENTENCE_ENDINGS as $ending) {
            $position = mb_strrpos($text, $ending);

            if ($position !== false && ($lastPosition === null || $position > $lastPosition)) {
                $lastPosition = $position;
            }
        }

        if ($lastPosition === null || $lastPosition <= $threshold) {
            return null;
        }

        return mb_substr($text, 0, $lastPosition + 1);
    }

    private function dispatchMessage(TelegraphChat $chat, string $content): void
    {
        $chat->message($content)->html()->dispatch(self::QUEUE_NAME);
    }
}
