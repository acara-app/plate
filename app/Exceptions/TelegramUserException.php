<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class TelegramUserException extends Exception
{
    public static function parsingFailed(): self
    {
        return new self('❌ Could not understand that. Try something like: "My glucose is 140" or "Took 5 units insulin"');
    }

    public static function savingFailed(): self
    {
        return new self('❌ Error saving log. Please try again.');
    }

    public static function processingFailed(): self
    {
        return new self('❌ Error processing message. Please try again.');
    }
}
