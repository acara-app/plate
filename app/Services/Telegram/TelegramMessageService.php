<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Models\TelegraphChat;
use Exception;
use Illuminate\Support\Facades\Log;

final class TelegramMessageService
{
    private const int MAX_MESSAGE_LENGTH = 4096;
    private const int CHUNK_DELAY_MS = 1000;
    private const int TYPING_INTERVAL_SECONDS = 4;
    private const int MAX_RETRIES = 4;
    private const int BASE_BACKOFF_MS = 1000;

    private bool $typingLoopActive = false;
    private ?int $typingTimerId = null;

    /**
     * Send a long message with automatic chunking, rate limiting, and retry logic.
     *
     * @param TelegraphChat $chat The Telegraph chat instance
     * @param string $message The full message to send
     * @param bool $markdown Whether to parse as Markdown
     * @throws Exception If all retries fail
     */
    public function sendLongMessage(TelegraphChat $chat, string $message, bool $markdown = true): void
    {
        $chunks = $this->splitMessage($message);

        foreach ($chunks as $index => $chunk) {
            $this->sendWithRetry($chat, $chunk, $markdown);

            // Add delay between chunks (skip for last chunk)
            if ($index < count($chunks) - 1) {
                usleep(self::CHUNK_DELAY_MS * 1000);
            }
        }
    }

    /**
     * Split a message into chunks at natural boundaries.
     *
     * @return array<int, string>
     */
    public function splitMessage(string $message): array
    {
        $message = trim($message);

        if (mb_strlen($message) <= self::MAX_MESSAGE_LENGTH) {
            return [$message];
        }

        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= self::MAX_MESSAGE_LENGTH) {
                $chunks[] = trim($remaining);
                break;
            }

            $chunk = $this->extractChunk($remaining);
            $chunks[] = trim($chunk);
            $remaining = trim(mb_substr($remaining, mb_strlen($chunk)));
        }

        return array_filter($chunks, fn(string $c) => $c !== '');
    }

    /**
     * Extract a single chunk from the beginning of the text.
     */
    private function extractChunk(string $text): string
    {
        $maxLen = self::MAX_MESSAGE_LENGTH;

        // Try to split at paragraph boundary (double newline)
        $searchText = mb_substr($text, 0, $maxLen);
        $lastParagraph = mb_strrpos($searchText, "\n\n");

        if ($lastParagraph !== false && $lastParagraph > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastParagraph);
        }

        // Try to split at line boundary
        $lastNewline = mb_strrpos($searchText, "\n");

        if ($lastNewline !== false && $lastNewline > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastNewline);
        }

        // Try to split at sentence boundary
        $lastSentence = $this->findLastSentenceEnd($searchText);

        if ($lastSentence !== false && $lastSentence > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastSentence + 1);
        }

        // Try to split at word boundary
        $lastSpace = mb_strrpos($searchText, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLen * 0.3) {
            return mb_substr($text, 0, $lastSpace);
        }

        // Force split at max length
        return $searchText;
    }

    /**
     * Find the last sentence-ending punctuation position.
     */
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

    /**
     * Send a single chunk with exponential backoff retry.
     *
     * @throws Exception If all retries fail
     */
    private function sendWithRetry(TelegraphChat $chat, string $chunk, bool $markdown): void
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $markdown
                    ? $chat->markdown($chunk)->send()
                    : $chat->message($chunk)->send();

                if ($response instanceof TelegraphResponse && $response->telegraphOk()) {
                    return;
                }

                // Check for rate limit (HTTP 429)
                if ($response->status() === 429) {
                    $retryAfter = $response->json('parameters.retry_after', 1);
                    Log::warning('Telegram rate limit hit', ['retry_after' => $retryAfter]);
                    sleep((int) $retryAfter);
                    $attempt++;
                    continue;
                }

                throw new Exception('Telegram send failed: ' . $response->body());
            } catch (Exception $e) {
                $attempt++;

                if ($attempt >= self::MAX_RETRIES) {
                    Log::error('Telegram message failed after retries', [
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                // Exponential backoff: 1s, 2s, 4s, 8s
                $backoffMs = self::BASE_BACKOFF_MS * (2 ** ($attempt - 1));
                Log::info('Telegram retry', ['attempt' => $attempt, 'backoff_ms' => $backoffMs]);
                usleep($backoffMs * 1000);
            }
        }
    }

    /**
     * Start sending periodic typing indicators.
     * Call this before starting AI generation.
     */
    public function startTypingLoop(TelegraphChat $chat): void
    {
        $this->typingLoopActive = true;

        // Send initial typing indicator
        $this->sendTypingAction($chat);
    }

    /**
     * Send typing action and schedule next one if loop is active.
     */
    public function sendTypingAction(TelegraphChat $chat): void
    {
        if (!$this->typingLoopActive) {
            return;
        }

        try {
            $chat->action('typing')->send();
        } catch (Exception $e) {
            Log::warning('Failed to send typing action', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Stop the typing indicator loop.
     */
    public function stopTypingLoop(): void
    {
        $this->typingLoopActive = false;
        $this->typingTimerId = null;
    }

    /**
     * Check if typing loop is active.
     */
    public function isTypingLoopActive(): bool
    {
        return $this->typingLoopActive;
    }

    /**
     * Get typing interval in seconds.
     */
    public static function getTypingIntervalSeconds(): int
    {
        return self::TYPING_INTERVAL_SECONDS;
    }

    /**
     * Get max message length constant.
     */
    public static function getMaxMessageLength(): int
    {
        return self::MAX_MESSAGE_LENGTH;
    }
}
