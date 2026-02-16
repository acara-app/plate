<?php

declare(strict_types=1);

use App\Services\Telegram\TelegramMessageService;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

beforeEach(function (): void {
    Telegraph::fake();
});

test('converts basic markdown to telegram compatible html', function (): void {
    $service = new TelegramMessageService();
    // Use reflection to access private method
    $reflection = new ReflectionClass(TelegramMessageService::class);
    $method = $reflection->getMethod('convertMarkdownToHtml');

    $markdown = "**Bold** and *Italic* and `code`";
    $html = $method->invoke($service, $markdown);

    // CommonMark uses <strong> for bold and <em> for italic
    // Our converter strips <p>
    expect($html)->toContain('<strong>Bold</strong>')
        ->toContain('<em>Italic</em>')
        ->toContain('<code>code</code>');
});

test('converts lists to telegram compatible format', function (): void {
    $service = new TelegramMessageService();
    $reflection = new ReflectionClass(TelegramMessageService::class);
    $method = $reflection->getMethod('convertMarkdownToHtml');

    $markdown = "- Item 1\n- Item 2";
    $html = $method->invoke($service, $markdown);

    expect($html)->toContain('• Item 1')
        ->toContain('• Item 2');
});

test('handles complex structure without excessive whitespace', function (): void {
    $service = new TelegramMessageService();
    $reflection = new ReflectionClass(TelegramMessageService::class);
    $method = $reflection->getMethod('convertMarkdownToHtml');

    $markdown = "Paragraph 1\n\n- Item 1\n- Item 2\n\nParagraph 2";
    $html = $method->invoke($service, $markdown);

    // We expect max 2 newlines ideally
    expect($html)->not->toContain("\n\n\n");
});

test('safe message length is respected', function (): void {
    $reflection = new ReflectionClass(TelegramMessageService::class);
    $constant = $reflection->getReflectionConstant('SAFE_MESSAGE_LENGTH');
    $safeLength = $constant->getValue();

    expect($safeLength)->toBe(3800);
    expect($safeLength)->toBeLessThan(TelegramMessageService::MAX_MESSAGE_LENGTH);
});

test('sends html message', function (): void {
    Telegraph::fake([
        DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE => ['ok' => true, 'result' => []],
    ]);

    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->for($bot, 'bot')->create();
    $service = new TelegramMessageService();

    $service->sendLongMessage($chat, '**Bold**', true);

    Telegraph::assertSentData(DefStudio\Telegraph\Telegraph::ENDPOINT_MESSAGE, [
        'text' => '<strong>Bold</strong>',
        'parse_mode' => 'html',
    ]);
});
