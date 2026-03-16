<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\History;

final class BuildConversationMessagesAction
{
    /**
     * @return list<array{id: string, role: string, parts: list<array<string, string>>}>
     */
    public function handle(?Conversation $conversation): array
    {
        if (! $conversation instanceof Conversation) {
            return [];
        }

        return array_values(
            $conversation->messages
                ->map(fn (History $message): array => [
                    'id' => $message->id,
                    'role' => $message->role->value,
                    'parts' => $this->buildParts($message),
                ])
                ->all()
        );
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildParts(History $message): array
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

        return [$textPart, ...$attachmentParts];
    }
}
