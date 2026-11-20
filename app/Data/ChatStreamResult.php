<?php

declare(strict_types=1);

namespace App\Data;

/**
 * @codeCoverageIgnore
 *
 * @phpstan-type TVisualPart array{type: 'data-spec', id: string, data: array{spec: array<string, mixed>, fallbackText: string}}
 */
final readonly class ChatStreamResult
{
    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     * @param  list<array<string, mixed>>  $providerTools
     * @param  list<array<string, mixed>>  $citations
     * @param  list<array<string, mixed>>  $errors
     * @param  array<string, mixed>  $usage
     * @param  array<string, string|null>  $pendingApprovals
     * @param  TVisualPart|null  $visualPart
     */
    public function __construct(
        public string $text = '',
        public array $toolCalls = [],
        public array $toolResults = [],
        public array $providerTools = [],
        public array $citations = [],
        public array $errors = [],
        public array $usage = [],
        public array $pendingApprovals = [],
        public ?array $visualPart = null,
    ) {}

    public function hasAssistantContent(): bool
    {
        return mb_trim($this->text) !== ''
            || $this->toolCalls !== []
            || $this->toolResults !== []
            || $this->providerTools !== []
            || $this->citations !== []
            || $this->visualPart !== null
            || $this->pendingApprovals !== [];
    }

    public function hasPendingApprovals(): bool
    {
        return $this->pendingApprovals !== [];
    }

    public function withoutPendingApprovals(): self
    {
        return new self(
            text: $this->text,
            toolCalls: $this->toolCalls,
            toolResults: $this->toolResults,
            providerTools: $this->providerTools,
            citations: $this->citations,
            errors: $this->errors,
            usage: $this->usage,
            visualPart: $this->visualPart,
        );
    }
}
