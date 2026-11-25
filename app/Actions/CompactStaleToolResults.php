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
        $callAgain = $this->callAgainTools();

        if ($callAgain === []) {
            return $messages;
        }

        return array_map(
            fn (Message $message): Message => $message instanceof ToolResultMessage
                ? new ToolResultMessage($message->toolResults->map(
                    fn (ToolResult $result): ToolResult => $result->successful() && isset($callAgain[$result->name])
                        ? $this->compacted($result, $callAgain[$result->name])
                        : $result,
                ))
                : $message,
            $messages,
        );
    }

    /**
     * @return array<string, string>
     */
    private function callAgainTools(): array
    {
        /** @var list<string> $turnScoped */
        $turnScoped = (array) config('altani.context.turn_scoped_tools', []);
        /** @var array<string, string> $retired */
        $retired = (array) config('altani.context.retired_tools', []);

        return [...array_combine($turnScoped, $turnScoped), ...$retired];
    }

    private function compacted(ToolResult $result, string $callAgain): ToolResult
    {
        return new ToolResult(
            id: $result->id,
            name: $result->name,
            arguments: $result->arguments,
            result: "Omitted to save context: this result is from an earlier turn. Call {$callAgain} again if you need it.",
            resultId: $result->resultId,
        );
    }
}
