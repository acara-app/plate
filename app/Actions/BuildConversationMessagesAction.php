<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\History;
use Illuminate\Support\Collection;
use Laravel\Ai\Messages\MessageRole;

final readonly class BuildConversationMessagesAction
{
    /**
     * @return list<array{id: string, role: string, parts: list<array<string, mixed>>}>
     */
    public function handle(?Conversation $conversation): array
    {
        if (! $conversation instanceof Conversation) {
            return [];
        }

        $approvals = $this->loadApprovals($conversation);

        return array_values(
            $conversation->messages
                ->map(fn (History $message): array => [
                    'id' => $message->id,
                    'role' => $message->role->value,
                    'parts' => $this->buildParts($message, $approvals),
                ])
                ->all()
        );
    }

    /**
     * @param  Collection<string, AgentApproval>  $approvals
     * @return list<array<string, mixed>>
     */
    private function buildParts(History $message, Collection $approvals): array
    {
        $textPart = ['type' => 'text', 'text' => $message->content];

        $attachmentParts = collect($message->attachments ?? [])
            ->map(function (array $attachment): array { // @phpstan-ignore argument.type
                $mime = isset($attachment['mime']) && is_string($attachment['mime'])
                    ? $attachment['mime']
                    : 'image/jpeg';

                $base64 = isset($attachment['base64']) && is_string($attachment['base64'])
                    ? $attachment['base64']
                    : '';

                return [
                    'type' => 'file',
                    'mediaType' => $mime,
                    'url' => sprintf('data:%s;base64,%s', $mime, $base64),
                ];
            })
            ->values()
            ->all();

        return [$textPart, ...$attachmentParts, ...$this->approvalParts($message, $approvals)];
    }

    /**
     * @return Collection<string, AgentApproval>
     */
    private function loadApprovals(Conversation $conversation): Collection
    {
        $ids = $conversation->messages
            ->flatMap(fn (History $message): array => $this->approvalIds($message))
            ->unique()
            ->all();

        /** @var Collection<string, AgentApproval> $approvals */
        $approvals = $ids === []
            ? collect()
            : AgentApproval::query()
                ->whereKey($ids)
                ->where('user_id', $conversation->user_id)
                ->get()
                ->keyBy('id');

        return $approvals;
    }

    /**
     * @param  Collection<string, AgentApproval>  $approvals
     * @return list<array<string, mixed>>
     */
    private function approvalParts(History $message, Collection $approvals): array
    {
        $parts = [];

        foreach ($this->approvalIds($message) as $approvalId) {
            $approval = $approvals->get($approvalId);

            if (! $approval instanceof AgentApproval) {
                continue;
            }

            /** @var array<string, mixed> $card */
            $card = $approval->toCardData()->toArray();

            $parts[] = [
                'type' => 'data-approval',
                'data' => [
                    'approvalId' => $approval->id,
                    'card' => $card,
                ],
            ];
        }

        return $parts;
    }

    /**
     * @return list<string>
     */
    private function approvalIds(History $message): array
    {
        if ($message->role !== MessageRole::Assistant) {
            return [];
        }

        $raw = $message->getRawOriginal('tool_results');
        $toolResults = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($toolResults)) {
            return [];
        }

        $ids = [];

        foreach ($toolResults as $toolResult) {
            if (! is_array($toolResult)) {
                continue;
            }

            if (($toolResult['name'] ?? null) !== 'log_health_entry') {
                continue;
            }

            $result = $toolResult['result'] ?? null;
            $decoded = is_string($result) ? json_decode($result, true) : null;
            $approvalId = is_array($decoded) ? ($decoded['approval_id'] ?? null) : null;

            if (is_string($approvalId)) {
                $ids[] = $approvalId;
            }
        }

        return $ids;
    }
}
