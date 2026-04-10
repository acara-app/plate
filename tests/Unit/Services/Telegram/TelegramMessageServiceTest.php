<?php

declare(strict_types=1);

use App\Services\Telegram\TelegramMessageService;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

covers(TelegramMessageService::class);

beforeEach(function (): void {
    Telegraph::fake();
});

describe('message chunking', function (): void {
    it('returns single chunk for short messages', function (): void {
        $service = new TelegramMessageService();

        $chunks = $service->splitMessage('Hello, world!');

        expect($chunks)->toHaveCount(1)
            ->and($chunks[0])->toBe('Hello, world!');
    });

    it('returns single chunk for message at max length', function (): void {
        $service = new TelegramMessageService();
        $message = str_repeat('a', TelegramMessageService::getSafeMessageLength());

        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(1)
            ->and(mb_strlen($chunks[0]))->toBe(TelegramMessageService::getSafeMessageLength());
    });

    it('splits at paragraph boundary when available', function (): void {
        $service = new TelegramMessageService();

        $paragraph1 = str_repeat('First paragraph. ', 150);
        $paragraph2 = str_repeat('Second paragraph. ', 150);
        $message = $paragraph1."\n\n".$paragraph2;

        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(2)
            ->and($chunks[0])->toBe(mb_trim($paragraph1))
            ->and($chunks[1])->toBe(mb_trim($paragraph2));
    });

    it('splits at line boundary when no paragraphs available', function (): void {
        $service = new TelegramMessageService();

        $line1 = str_repeat('First line content. ', 130);
        $line2 = str_repeat('Second line content. ', 130);
        $message = $line1."\n".$line2;

        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(2)
            ->and($chunks[0])->toBe(mb_trim($line1))
            ->and($chunks[1])->toBe(mb_trim($line2));
    });

    it('splits at sentence boundary when no line breaks available', function (): void {
        $service = new TelegramMessageService();

        $sentence1 = str_repeat('A ', 1800);
        $sentence2 = str_repeat('B ', 500);
        $message = $sentence1.'. '.$sentence2;

        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(2)
            ->and($chunks[0])->toEndWith('.')
            ->and(mb_strlen($chunks[0]))->toBeLessThanOrEqual(TelegramMessageService::getSafeMessageLength());
    });

    it('splits at word boundary as fallback', function (): void {
        $service = new TelegramMessageService();

        $message = str_repeat('word ', 1000);
        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(2);
        foreach ($chunks as $chunk) {
            expect(mb_strlen($chunk))->toBeLessThanOrEqual(TelegramMessageService::getSafeMessageLength());
        }
    });

    it('force splits when no boundaries available', function (): void {
        $service = new TelegramMessageService();

        $message = str_repeat('x', 5000);

        $chunks = $service->splitMessage($message);

        expect($chunks)->toHaveCount(2)
            ->and(mb_strlen($chunks[0]))->toBe(TelegramMessageService::getSafeMessageLength())
            ->and(mb_strlen($chunks[1]))->toBe(5000 - TelegramMessageService::getSafeMessageLength());
    });

    it('handles empty message', function (): void {
        $service = new TelegramMessageService();

        $chunks = $service->splitMessage('');

        expect($chunks)->toHaveCount(1)
            ->and($chunks[0])->toBe('');
    });

    it('handles whitespace-only message', function (): void {
        $service = new TelegramMessageService();

        $chunks = $service->splitMessage('   ');

        expect($chunks)->toHaveCount(1)
            ->and($chunks[0])->toBe('');
    });

    it('chunks very long multi-paragraph message correctly', function (): void {
        $service = new TelegramMessageService();

        $paragraphs = [];
        for ($i = 1; $i <= 5; $i++) {
            $paragraphs[] = str_repeat(sprintf('Paragraph %d. ', $i), 60);
        }

        $message = implode("\n\n", $paragraphs);

        $chunks = $service->splitMessage($message);

        foreach ($chunks as $chunk) {
            expect(mb_strlen($chunk))->toBeLessThanOrEqual(TelegramMessageService::getSafeMessageLength());
        }

        $reconstructed = implode("\n\n", $chunks);
        expect(mb_strlen($reconstructed))->toBeGreaterThanOrEqual(mb_strlen($message) - 100);
    });
});

describe('message sending', function (): void {
    it('sends short message in single call', function (): void {
        Telegraph::fake([
            DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE => ['ok' => true, 'result' => []],
        ]);

        $bot = TelegraphBot::factory()->create();
        $chat = TelegraphChat::factory()->for($bot, 'bot')->create();
        $service = new TelegramMessageService();

        $service->sendLongMessage($chat, 'Hello, world!', false);

        Telegraph::assertSent('Hello, world!');
    });

    it('sends markdown message correctly', function (): void {
        Telegraph::fake([
            DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE => ['ok' => true, 'result' => []],
        ]);

        $bot = TelegraphBot::factory()->create();
        $chat = TelegraphChat::factory()->for($bot, 'bot')->create();
        $service = new TelegramMessageService();

        $service->sendLongMessage($chat, '**Bold text**');

        Telegraph::assertSentData(DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE, [
            'text' => '<strong>Bold text</strong>',
            'parse_mode' => 'html',
        ]);
    });

    it('sends chunked messages for long content', function (): void {
        Telegraph::fake([
            DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE => ['ok' => true, 'result' => []],
        ]);

        $bot = TelegraphBot::factory()->create();
        $chat = TelegraphChat::factory()->for($bot, 'bot')->create();
        $service = new TelegramMessageService();

        $longMessage = str_repeat('Test content. ', 400);
        $service->sendLongMessage($chat, $longMessage, false);

        Telegraph::assertSentData(DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE);
    });
});

describe('typing indicator', function (): void {
    it('sends typing indicator once', function (): void {
        Telegraph::fake();

        $bot = TelegraphBot::factory()->create();
        $chat = TelegraphChat::factory()->for($bot, 'bot')->create();
        $service = new TelegramMessageService();

        $service->sendTypingIndicator($chat);

        Telegraph::assertSentData(DefStudio\Telegraph\Telegraph::ENDPOINT_SEND_CHAT_ACTION, [
            'action' => 'typing',
        ]);
    });
});

describe('max message length', function (): void {
    it('returns 4096 as max message length', function (): void {
        expect(TelegramMessageService::getMaxMessageLength())->toBe(4096);
    });
});
