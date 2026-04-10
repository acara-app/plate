<?php

declare(strict_types=1);

use App\Exceptions\TelegramUserException;

covers(TelegramUserException::class);

it('parsingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::parsingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Could not understand that');
});

it('savingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::savingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Error saving log');
});

it('processingFailed returns correct exception', function (): void {
    $exception = TelegramUserException::processingFailed();

    expect($exception)->toBeInstanceOf(TelegramUserException::class)
        ->getMessage()->toContain('Error processing message');
});
