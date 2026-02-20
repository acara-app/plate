<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class IndexNowResultData
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public bool $success,
        public int $urlsSubmitted = 0,
        public string $message = '',
        public array $errors = [],
    ) {}

    public static function success(int $urlsSubmitted, string $message = ''): self
    {
        return new self(
            success: true,
            urlsSubmitted: $urlsSubmitted,
            message: $message ?: sprintf('Successfully submitted %d URLs to IndexNow.', $urlsSubmitted),
        );
    }

    public static function noKey(): self
    {
        return new self(
            success: false,
            message: 'IndexNow key is not configured. Set INDEXNOW_KEY in your environment.',
        );
    }

    public static function noUrls(): self
    {
        return new self(
            success: true,
            message: 'No URLs to submit.',
        );
    }

    /**
     * @param  array<int, string>  $errors
     */
    public static function failure(string $message, array $errors = [], int $urlsSubmitted = 0): self
    {
        return new self(
            success: false,
            urlsSubmitted: $urlsSubmitted,
            message: $message,
            errors: $errors,
        );
    }
}
