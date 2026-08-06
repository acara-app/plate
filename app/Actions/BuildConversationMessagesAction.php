<?php

declare(strict_types=1);

namespace App\Actions;

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

        return array_values(
            $conversation->messages
                ->reject(fn (History $message): bool => $message->isPendingStreamAssistant())
                ->map(fn (History $message): array => [
                    'id' => $message->id,
                    'role' => $message->role->value,
                    'parts' => $this->buildParts($message),
                ])
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildParts(History $message): array
    {
        $textPart = ['type' => 'text', 'text' => $message->content];

        $attachmentParts = collect($message->attachments ?? [])
            ->map(function (array $attachment): array {
                $mime = $attachment['mime'] ?? 'image/jpeg';

                return [
                    'type' => 'file',
                    'mediaType' => $mime,
                    'url' => sprintf('data:%s;base64,%s', $mime, $attachment['base64'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return [$textPart, ...$attachmentParts, ...$this->approvalParts($message)];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function approvalParts(History $message): array
    {
        if ($message->role !== MessageRole::Assistant) {
            return [];
        }

        $requested = $message->requestedApprovals();

        if ($requested === []) {
            return [];
        }

        $pending = $message->pendingApprovals();
        $recorded = $message->recordedApprovalDecisions();

        $toolCalls = collect($message->tool_calls ?? [])->keyBy('id');

        $results = collect($message->tool_results ?? [])->keyBy('id');

        $parts = [];

        foreach ($requested as $toolCallId => $reason) {
            /** @var array<string, mixed> $toolCall */
            $toolCall = $toolCalls->get($toolCallId, []);

            $parts[] = [
                'type' => 'data-approval',
                'data' => [
                    'toolCallId' => $toolCallId,
                    'tool' => $toolCall['name'] ?? '',
                    'reason' => $reason,
                    'arguments' => $toolCall['arguments'] ?? [],
                    'status' => $this->statusFor($toolCallId, $pending, $recorded, $results),
                ],
            ];
        }

        return $parts;
    }

    /**
     * @param  array<string, string|null>  $pending
     * @param  array<string, array{action: string, result?: string|null}>  $recorded
     * @param  Collection<int|string, array{id: string, name: string, arguments?: array<string, mixed>|null, result?: mixed, result_id?: string|null, denied?: bool}>  $results
     */
    private function statusFor(string $toolCallId, array $pending, array $recorded, Collection $results): string
    {
        if (array_key_exists($toolCallId, $pending)) {
            return array_key_exists($toolCallId, $recorded) ? 'submitted' : 'pending';
        }

        $result = $results->get($toolCallId);

        return match (true) {
            $result === null => 'abandoned',
            ($result['denied'] ?? false) === true => 'rejected',
            default => 'approved',
        };
    }
}
