<?php

declare(strict_types=1);

namespace App\Actions;

use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Responses\Data\ToolResult;

final readonly class CompactStaleToolResults
{
    /**
     * @param  list<Message>  $messages
     * @return list<Message>
     */
    public function handle(array $messages): array
    {
        $turnScopedTools = (array) config('altani.context.turn_scoped_tools', []);

        if ($turnScopedTools === []) {
            return $messages;
        }

        return array_map(
            fn (Message $message): Message => $message instanceof ToolResultMessage
                ? new ToolResultMessage($message->toolResults->map(
                    fn (ToolResult $result): ToolResult => $result->successful() && in_array($result->name, $turnScopedTools, true)
                        ? $this->compacted($result)
                        : $result,
                ))
                : $message,
            $messages,
        );
    }

    private function compacted(ToolResult $result): ToolResult
    {
        return new ToolResult(
            id: $result->id,
            name: $result->name,
            arguments: $result->arguments,
            result: "Omitted to save context: this result is from an earlier turn. Call {$result->name} again if you need it.",
            resultId: $result->resultId,
        );
    }
}
