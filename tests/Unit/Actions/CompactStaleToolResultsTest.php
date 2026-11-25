<?php

declare(strict_types=1);

use App\Actions\CompactStaleToolResults;
use Illuminate\Support\Collection;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;

covers(CompactStaleToolResults::class);

beforeEach(function (): void {
    config(['altani.context.turn_scoped_tools' => ['activate_skill']]);
});

it('replaces earlier-turn skill instructions with a stub the model can act on', function (): void {
    $question = new Message(MessageRole::User, 'Analyze my lunch');
    $toolCalls = new AssistantMessage('', new Collection([new ToolCall('call-1', 'activate_skill', ['skillName' => 'nutrition-analyzer'])]));
    $skillResult = new ToolResult('call-1', 'activate_skill', ['skillName' => 'nutrition-analyzer'], str_repeat('Weigh every ingredient. ', 1200), 'result-1');

    $messages = resolve(CompactStaleToolResults::class)->handle([
        $question,
        $toolCalls,
        new ToolResultMessage(new Collection([$skillResult])),
    ]);

    $compacted = $messages[2]->toolResults->first();

    expect($messages[0])->toBe($question)
        ->and($messages[1])->toBe($toolCalls)
        ->and($compacted->id)->toBe('call-1')
        ->and($compacted->resultId)->toBe('result-1')
        ->and($compacted->arguments)->toBe(['skillName' => 'nutrition-analyzer'])
        ->and($compacted->result)->toBe('Omitted to save context: this result is from an earlier turn. Call activate_skill again if you need it.');
});

it('leaves other tools and unsuccessful skill calls untouched', function (ToolResult $result): void {
    $messages = resolve(CompactStaleToolResults::class)->handle([
        new ToolResultMessage(new Collection([$result])),
    ]);

    expect($messages[0]->toolResults->first())->toBe($result);
})->with([
    'a tool that is not turn-scoped' => [new ToolResult('call-1', 'get_user_profile', [], ['age' => 34])],
    'a denied skill call' => [new ToolResult('call-1', 'activate_skill', [], 'Denied by user.', denied: true)],
    'a failed skill call' => [new ToolResult('call-1', 'activate_skill', [], "Skill 'unknown' not found.", failed: true)],
]);

it('returns the history untouched when no tools are turn-scoped', function (): void {
    config(['altani.context.turn_scoped_tools' => []]);

    $history = [new ToolResultMessage(new Collection([new ToolResult('call-1', 'activate_skill', [], 'Full instructions')]))];

    expect(resolve(CompactStaleToolResults::class)->handle($history))->toBe($history);
});
