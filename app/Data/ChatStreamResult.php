<?php

declare(strict_types=1);

namespace App\Data;

/** @codeCoverageIgnore */
final readonly class ChatStreamResult
{
    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     * @param  list<array<string, mixed>>  $providerTools
     * @param  list<array<string, mixed>>  $citations
     * @param  list<array<string, mixed>>  $errors
     * @param  array<string, mixed>  $usage
     */
    public function __construct(
        public string $text = '',
        public array $toolCalls = [],
        public array $toolResults = [],
        public array $providerTools = [],
        public array $citations = [],
        public array $errors = [],
        public array $usage = [],
    ) {}

    public function hasAssistantContent(): bool
    {
        return mb_trim($this->text) !== ''
            || $this->toolCalls !== []
            || $this->toolResults !== []
            || $this->providerTools !== []
            || $this->citations !== [];
    }
}
