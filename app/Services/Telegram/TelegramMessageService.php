<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Sleep;
use League\CommonMark\GithubFlavoredMarkdownConverter;

final class TelegramMessageService
{
    public const int MAX_MESSAGE_LENGTH = 4096;

    private const int SAFE_MESSAGE_LENGTH = 3800;

    private const int CHUNK_DELAY_MS = 1000;

    private const string QUEUE_NAME = 'telegram';

    private const float MIN_SPLIT_THRESHOLD = 0.3;

    private const array SENTENCE_ENDINGS = ['. ', '! ', '? ', ".\n", "!\n", "?\n"];

    private const array SPLIT_PRIORITIES = ["\n\n", "\n"];

    public static function getMaxMessageLength(): int
    {
        return self::MAX_MESSAGE_LENGTH;
    }

    public static function getSafeMessageLength(): int
    {
        return self::SAFE_MESSAGE_LENGTH;
    }

    public function sendLongMessage(TelegraphChat $chat, string $message, bool $markdown = true): void
    {
        $chunks = $this->splitMessage($message);

        foreach ($chunks as $index => $chunk) {
            $this->dispatchMessage($chat, $chunk, $markdown);

            if ($index < count($chunks) - 1) {
                Sleep::usleep(self::CHUNK_DELAY_MS * 1000);
            }
        }
    }

    /**
     * @return array<string>
     */
    public function splitMessage(string $message): array
    {
        $message = mb_trim($message);

        if (mb_strlen($message) <= self::SAFE_MESSAGE_LENGTH) {
            return [$message];
        }

        return $this->chunkMessage($message);
    }

    public function sendTypingIndicator(TelegraphChat $chat): void
    {
        $chat->action(ChatActions::TYPING)->send();
    }

    /**
     * @return array<string>
     */
    private function chunkMessage(string $message): array
    {
        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= self::SAFE_MESSAGE_LENGTH) {
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
        $maxLength = self::SAFE_MESSAGE_LENGTH;
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

    private function dispatchMessage(TelegraphChat $chat, string $chunk, bool $markdown): void
    {
        if ($markdown) {
            $html = $this->convertMarkdownToHtml($chunk);
            $chat->html($html)->send();

            return;
        }

        $chat->message($chunk)->send();
    }

    private function convertMarkdownToHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($markdown)->getContent();

        // Handle lists - convert <li> to bullets
        $html = str_replace('<li>', '• ', $html);
        $html = str_replace('</li>', "\n", $html);
        $html = str_replace(['<ul>', '<ol>', '</ul>', '</ol>'], ["\n", "\n", "\n", "\n"], $html);

        // Convert <p> tags to double newlines
        $html = str_replace(
            ['<p>', '</p>'],
            ['', "\n\n"],
            $html
        );

        // Convert <br> to newline
        $html = str_replace(['<br>', '<br />'], "\n", $html);

        // Strip unsupported tags
        // Telegram supports: <b>, <strong>, <i>, <em>, <u>, <ins>, <s>, <strike>, <del>,
        // <span class="tg-spoiler">, <a>, <code>, <pre>, <blockquote>
        return trim(strip_tags($html, '<b><strong><i><em><u><ins><s><strike><del><a><code><pre><blockquote>'));
    }
}
