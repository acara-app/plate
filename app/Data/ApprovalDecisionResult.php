<?php

declare(strict_types=1);

namespace App\Data;

/** @codeCoverageIgnore */
final readonly class ApprovalDecisionResult
{
    /**
     * @param  list<string>  $awaiting  tool call IDs on the turn still without a decision
     */
    public function __construct(
        public ?ChatStreamTurn $turn,
        public array $awaiting,
    ) {}

    public function resumed(): bool
    {
        return $this->turn instanceof ChatStreamTurn;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(int $userId, string $conversationId): array
    {
        if (! $this->turn instanceof ChatStreamTurn) {
            return [
                'status' => 'recorded',
                'conversationId' => $conversationId,
                'awaiting' => $this->awaiting,
            ];
        }

        return $this->turn->acceptedPayload($userId, $conversationId);
    }
}
