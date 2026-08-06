<?php

declare(strict_types=1);

namespace App\Data;

use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;

final readonly class RecordedApprovalDecisions
{
    /**
     * @param  array<string, array{action: string, result?: string|null}>  $decisions  every decision recorded on the turn so far
     * @param  list<string>  $awaiting  tool call IDs on the turn still without a decision
     */
    public function __construct(
        public array $decisions,
        public array $awaiting,
    ) {}

    public function complete(): bool
    {
        return $this->awaiting === [];
    }

    public function toDecisions(): Decisions
    {
        $decisions = [];

        foreach ($this->decisions as $toolCallId => $decision) {
            $decisions[$toolCallId] = $decision['action'] === 'approve'
                ? Decision::approve()
                : Decision::reject($decision['result'] ?? null);
        }

        return Decisions::from($decisions);
    }

    /**
     * @return list<array{id: string, action: string, result: string|null}>
     */
    public function toQueuePayload(): array
    {
        $normalized = [];

        foreach ($this->decisions as $toolCallId => $decision) {
            $normalized[] = [
                'id' => (string) $toolCallId,
                'action' => $decision['action'],
                'result' => $decision['result'] ?? null,
            ];
        }

        return $normalized;
    }
}
