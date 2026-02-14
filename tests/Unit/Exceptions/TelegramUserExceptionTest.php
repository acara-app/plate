<?php

declare(strict_types=1);

use App\Exceptions\TelegramUserException;

test('parsingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::parsingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Could not understand that');
});

test('savingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::savingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Error saving log');
});

test('processingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::processingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Error processing message');
});
