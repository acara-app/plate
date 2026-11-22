<?php

declare(strict_types=1);

namespace App\Data;

use App\Services\ReplyComposer;

/**
 * @codeCoverageIgnore
 *
 * @phpstan-type TReplyPart array{type: 'text', text: string}|array{type: 'data-spec', data: array{id: string, spec: string, fallback_text: string}}
 * @phpstan-type TVisualOutcome array{id: string, accepted: bool, reason: string|null}
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
     * @param  list<TReplyPart>  $parts  the reply in order when it holds an accepted visual, each spec JSON-encoded
     * @param  list<TVisualOutcome>  $visuals
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
        public array $parts = [],
        public array $visuals = [],
    ) {}

    public function hasAssistantContent(): bool
    {
        return mb_trim($this->text) !== ''
            || $this->toolCalls !== []
            || $this->toolResults !== []
            || $this->providerTools !== []
            || $this->citations !== []
            || $this->pendingApprovals !== []
            || $this->parts !== [];
    }

    public function hasPendingApprovals(): bool
    {
        return $this->pendingApprovals !== [];
    }

    public function contextText(): string
    {
        if ($this->parts === []) {
            return $this->text;
        }

        return implode('', array_map(
            fn (array $part): string => $part['type'] === 'text' ? $part['text'] : ReplyComposer::contextMarker($part['data']['fallback_text']),
            $this->parts,
        ));
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
            parts: $this->parts,
            visuals: $this->visuals,
        );
    }
}
