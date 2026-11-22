<?php

declare(strict_types=1);

use App\Actions\ApplyJsonPatch;
use App\Services\ReplyComposer;

covers(ReplyComposer::class);

it('keeps prose and a visual in the order Altani wrote them', function (): void {
    $events = composeReply(implode("\n", [
        'Here is your breakfast.',
        '```spec',
        '{"op":"add","path":"/root","value":"card"}',
        '{"op":"add","path":"/elements/card","value":{"type":"Card","props":{"title":"Breakfast"},"children":["macros","line"]}}',
        '{"op":"add","path":"/elements/macros","value":{"type":"MacroBreakdown","props":{"calories":410,"carbs":50,"protein":21,"fat":14},"children":[]}}',
        '{"op":"add","path":"/elements/line","value":{"type":"Separator","props":{},"children":[]}}',
        '```',
        'That keeps you full until lunch.',
    ]));
    $patches = array_merge(...array_column(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_patch'), 'patches'));
    $end = array_values(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_end'));

    expect(composedText($events))->toBe("Here is your breakfast.\nThat keeps you full until lunch.")
        ->and(eventOrder($events))->toBe(['text', 'placeholder', 'visual_patch', 'visual_end', 'text'])
        ->and(json_encode($patches, JSON_UNESCAPED_SLASHES))->toContain('{"op":"add","path":"/elements","value":{}}')
        ->and(json_encode(reassemble($patches), JSON_UNESCAPED_SLASHES))->toBe(
            '{"root":"card","elements":{"card":{"type":"Card","props":{"title":"Breakfast"},"children":["macros","line"]},'
            .'"macros":{"type":"MacroBreakdown","props":{"calories":410,"carbs":50,"protein":21,"fat":14},"children":[]},'
            .'"line":{"type":"Separator","props":{},"children":[]}}}',
        )
        ->and($end)->toBe([[
            'type' => 'visual_end',
            'visual_id' => 'reply-1',
            'accepted' => true,
            'fallback_text' => "Breakfast\n410 kcal: carbs 50 g, protein 21 g, fat 14 g",
            'reason' => null,
        ]]);
});

it('passes a prose-only reply through unchanged', function (): void {
    $reply = "No visual here.\nUse `inline code` freely.\n    An indented line\n```python\nprint('hi')\n```";
    $events = composeReply($reply);

    expect(composedText($events))->toBe($reply)
        ->and(eventOrder($events))->toBe(['text']);
});

it('withholds a visual it cannot accept and keeps the prose', function (string $reply, string $reason): void {
    $events = composeReply("Before.\n{$reply}After.");
    $rejection = array_values(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_end' && ! $event['accepted']));

    expect(composedText($events))->toBe("Before.\nAfter.")
        ->and($rejection)->toHaveCount(1)
        ->and($rejection[0]['reason'])->toBe($reason);
})->with([
    'a line that is not valid JSON' => ["```spec\n{not json\n```\n", 'malformed_patch'],
    'an unknown component' => ["```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Chart\",\"props\":{},\"children\":[]}}\n```\n", 'unknown_component'],
    'a seventh visual' => [fn (): string => str_repeat("```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Hi\"},\"children\":[]}}\n```\n", 7), 'too_many_visuals'],
    'more patches than the iOS app applies' => [fn (): string => yearCharts(6), 'too_large'],
    'a text rendering too long for one event' => [fn (): string => yearCharts(1), 'too_large'],
]);

it('starts every text block at the beginning of a line', function (): void {
    $visual = "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Hi\"},\"children\":[]}}\n```\n";
    $composer = resolve(ReplyComposer::class, replyComposerOptions());
    $events = [...$composer->feed('Let me check.'), ...$composer->finish(), ...$composer->feed($visual.'Done.'), ...$composer->finish()];
    $steps = resolve(ReplyComposer::class, replyComposerOptions());

    expect(eventOrder($events))->toBe(['text', 'placeholder', 'visual_patch', 'visual_end', 'text'])
        ->and(composedText($events))->toBe('Let me check.Done.')
        ->and([$steps->contextText('Let me check.'), $steps->contextText($visual.'Done.')])->toBe(['Let me check.', ReplyComposer::contextMarker('Hi').'Done.']);
});

it('closes a visual Altani forgot to fence off and drops one cut off by a stop', function (): void {
    $visual = "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Hi\"},\"children\":[]}}";
    $finished = composeReply("Here you go.\n{$visual}");
    $continued = composeReply("Here you go.\n{$visual}\nWalk after lunch too.");
    $noted = composeReply("Here you go.\n{$visual}\nNote: steps count from midnight.\n```\nWalk after lunch too.");

    $stopped = resolve(ReplyComposer::class, replyComposerOptions());
    $stopped->feed("Here you go.\n{$visual}");

    expect(end($finished))->toMatchArray(['type' => 'visual_end', 'accepted' => true, 'fallback_text' => 'Hi'])
        ->and(eventOrder($continued))->toBe(['text', 'placeholder', 'visual_patch', 'visual_end', 'text'])
        ->and(composedText($continued))->toBe("Here you go.\nWalk after lunch too.")
        ->and(composedText($noted))->toBe("Here you go.\nNote: steps count from midnight.\nWalk after lunch too.")
        ->and($stopped->cancel())->toBe([[
            'type' => 'visual_end',
            'visual_id' => 'reply-1',
            'accepted' => false,
            'fallback_text' => '',
            'reason' => 'cancelled',
        ]]);
});

it('streams a large visual in events that fit the broadcast limit', function (): void {
    $days = array_map(fn (int $day): array => ['day' => now()->startOfYear()->addDays($day)->toDateString(), 'steps' => $day * 37], range(0, 179));
    $chart = ['type' => 'BarChart', 'props' => ['title' => 'Daily steps', 'data' => $days, 'xKey' => 'day', 'yKey' => 'steps', 'unit' => 'steps'], 'children' => []];
    $events = composeReply("```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"chart\"}\n".json_encode(['op' => 'add', 'path' => '/elements/chart', 'value' => $chart])."\n```\n", 512);
    $releases = array_values(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_patch' && $event['patches'] !== []));

    expect(count($releases))->toBeGreaterThan(1)
        ->and(array_map(fn (array $event): int => mb_strlen((string) json_encode((string) json_encode($event))), $events))
        ->each->toBeLessThanOrEqual(ReplyComposer::EVENT_BUDGET_BYTES)
        ->and(reassemble(array_merge(...array_column($releases, 'patches')))->elements->chart->props->data)->toHaveCount(180)
        ->and(end($events))->toMatchArray(['type' => 'visual_end', 'accepted' => true]);
});

/**
 * @return array{catalogVersion: int, locale: string, timezone: string, visualIdPrefix: string}
 */
function replyComposerOptions(): array
{
    return ['catalogVersion' => 1, 'locale' => 'en', 'timezone' => 'UTC', 'visualIdPrefix' => 'reply'];
}

/**
 * @return list<array<string, mixed>>
 */
function composeReply(string $reply, int $sliceLength = 7): array
{
    $composer = resolve(ReplyComposer::class, replyComposerOptions());
    $events = [];

    foreach (mb_str_split($reply, $sliceLength) as $slice) {
        array_push($events, ...$composer->feed($slice));
    }

    return [...$events, ...$composer->finish()];
}

/**
 * @param  list<array<string, mixed>>  $events
 */
function composedText(array $events): string
{
    return implode('', array_column(array_filter($events, fn (array $event): bool => $event['type'] === 'text'), 'text'));
}

/**
 * @param  list<array<string, mixed>>  $events
 * @return list<string>
 */
function eventOrder(array $events): array
{
    $order = [];

    foreach ($events as $event) {
        $kind = $event['type'] === 'visual_patch' && $event['patches'] === [] ? 'placeholder' : $event['type'];

        if (end($order) !== $kind) {
            $order[] = $kind;
        }
    }

    return $order;
}

/**
 * @param  list<array<string, mixed>>  $patches
 */
function reassemble(array $patches): stdClass
{
    return array_reduce(
        $patches,
        fn (mixed $document, array $patch): mixed => (new ApplyJsonPatch)->handle($document, json_decode((string) json_encode($patch))),
        new stdClass,
    );
}

function yearCharts(int $count): string
{
    $year = array_map(fn (int $day): array => ['day' => now()->startOfYear()->addDays($day)->toDateString(), 'steps' => $day], range(0, 365));
    $charts = array_map(fn (int $index): string => "chart-{$index}", range(1, $count));
    $lines = [
        '{"op":"add","path":"/root","value":"charts"}',
        json_encode(['op' => 'add', 'path' => '/elements/charts', 'value' => ['type' => 'Stack', 'props' => (object) [], 'children' => $charts]]),
    ];

    foreach ($charts as $chart) {
        $lines[] = json_encode(['op' => 'add', 'path' => "/elements/{$chart}", 'value' => ['type' => 'BarChart', 'props' => ['title' => 'Steps', 'data' => $year, 'xKey' => 'day', 'yKey' => 'steps', 'unit' => 'steps'], 'children' => []]]);
    }

    return "```spec\n".implode("\n", $lines)."\n```\n";
}
