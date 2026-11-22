<?php

declare(strict_types=1);

use App\Actions\ApplyJsonPatch;
use App\Data\VisualDataset;
use App\Enums\GlucoseUnit;
use App\Services\ReplyComposer;
use App\Services\TurnDataRegistry;

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
    'a text rendering too long for one event' => [fn (): string => paragraphs(40), 'too_large'],
    'data this turn did not fetch' => ["```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"BarChart\",\"props\":{\"title\":\"Steps\",\"data\":{\"\$state\":\"/data/step_count\"},\"xKey\":\"day\",\"yKey\":\"value\",\"unit\":\"steps\"},\"children\":[]}}\n```\n", 'unresolved_reference'],
    'data Altani wrote itself' => ["```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/state/data\",\"value\":{\"step_count\":[{\"day\":\"2026-11-16\",\"value\":99999}]}}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"BarChart\",\"props\":{\"title\":\"Steps\",\"data\":{\"\$state\":\"/data/step_count\"},\"xKey\":\"day\",\"yKey\":\"value\",\"unit\":\"steps\"},\"children\":[]}}\n```\n", 'state_outside_ui'],
]);

it('binds the rows a tool fetched this turn and lets Plate supply their keys, unit and day boundary', function (): void {
    $events = withSteps(['2026-11-16' => 4200, '2026-11-17' => 0, '2026-11-18' => null], fn (): array => composeReply(implode("\n", [
        'Your week:',
        '```spec',
        '{"op":"add","path":"/root","value":"steps"}',
        '{"op":"add","path":"/elements/steps","value":{"type":"BarChart","props":{"title":"Daily steps","data":{"$state":"/data/step_count"},"xKey":"date","yKey":"steps","unit":"km","dayBoundary":"Local days"},"children":[]}}',
        '```',
    ])));
    $spec = reassemble(array_merge(...array_column(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_patch'), 'patches')));

    expect(json_encode($spec->state, JSON_UNESCAPED_SLASHES))->toBe('{"data":{"step_count":[{"day":"2026-11-16","value":4200},{"day":"2026-11-17","value":0},{"day":"2026-11-18","value":null}]}}')
        ->and(array_values(array_intersect_key((array) $spec->elements->steps->props, array_flip(['xKey', 'yKey', 'unit', 'dayBoundary']))))->toBe(['day', 'value', 'steps', 'Each day runs from midnight to midnight UTC.'])
        ->and(end($events))->toMatchArray([
            'type' => 'visual_end',
            'accepted' => true,
            'fallback_text' => "Daily steps\n- Mon Nov 16, 2026: 4,200 steps\n- Tue Nov 17, 2026: 0 steps\n- Wed Nov 18, 2026: no reading\nEach day runs from midnight to midnight UTC.",
        ]);
});

it('releases a year of bound rows in events that fit and sums them up in Mongolian', function (): void {
    $days = array_map(fn (int $day): string => now()->setDate(2025, 11, 22)->addDays($day)->toDateString(), range(0, 365));
    $steps = array_combine($days, array_map(fn (int $day): ?int => $day % 10 === 3 ? null : 3000 + $day * 10, range(0, 365)));
    $events = withSteps($steps, fn (): array => composeReply(implode("\n", [
        '```spec',
        '{"op":"add","path":"/root","value":"steps"}',
        '{"op":"add","path":"/elements/steps","value":{"type":"BarChart","props":{"title":"Алхам","data":{"$state":"/data/step_count"},"xKey":"day","yKey":"value","unit":"steps"},"children":[]}}',
        '```',
    ]), options: ['locale' => 'mn']));
    $releases = array_values(array_filter($events, fn (array $event): bool => $event['type'] === 'visual_patch' && $event['patches'] !== []));

    expect(count($releases))->toBeGreaterThan(1)
        ->and(array_map(fn (array $event): int => mb_strlen((string) json_encode((string) json_encode($event))), $events))->each->toBeLessThanOrEqual(ReplyComposer::EVENT_BUDGET_BYTES)
        ->and(json_decode((string) json_encode(reassemble(array_merge(...array_column($releases, 'patches')))->state->data->step_count), true))
        ->toBe(array_map(fn (string $day, ?int $value): array => ['day' => $day, 'value' => $value], $days, array_values($steps)))
        ->and(end($events))->toMatchArray([
            'type' => 'visual_end',
            'accepted' => true,
            'fallback_text' => "Алхам\nБя 2025 11 сар 22 – Ня 2026 11 сар 22\nДундаж: 4,824.44 алхам\nХамгийн их: 6,650 алхам (Ня 2026 11 сар 22)\nХамгийн бага: 3,000 алхам (Бя 2025 11 сар 22)\nХэмжилт байхгүй: 37 (нийт 366)\nӨдөр бүрийг UTC цагаар шөнө дундаас шөнө дунд хүртэл тооцно.",
        ]);
});

it('starts every text block at the beginning of a line', function (): void {
    $visual = "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Hi\"},\"children\":[]}}\n```\n";
    $composer = resolve(ReplyComposer::class, replyComposerOptions());
    $events = [...$composer->feed('Let me check.'), ...$composer->finish(), ...$composer->feed($visual.'Done.'), ...$composer->finish()];
    $steps = resolve(ReplyComposer::class, replyComposerOptions());

    expect(eventOrder($events))->toBe(['text', 'placeholder', 'visual_patch', 'visual_end', 'text'])
        ->and(composedText($events))->toBe('Let me check.Done.')
        ->and([$steps->contextText('Let me check.'), $steps->contextText($visual.'Done.')])->toBe(['Let me check.', ReplyComposer::contextMarker('Hi').'Done.']);
});

it('accepts follow-up chips and keeps them out of the text later turns read', function (): void {
    $chips = "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"next\"}\n{\"op\":\"add\",\"path\":\"/elements/next\",\"value\":{\"type\":\"SuggestionChips\",\"props\":{\"items\":[{\"label\":\"Plan lunch\",\"action\":{\"action\":\"sendMessage\",\"params\":{\"text\":\"Plan a light lunch for me.\"}}}]},\"children\":[]}}\n```\n";
    $events = composeReply("Enjoy your walk.\n{$chips}");

    expect(end($events))->toMatchArray(['type' => 'visual_end', 'accepted' => true, 'fallback_text' => ''])
        ->and(resolve(ReplyComposer::class, replyComposerOptions())->contextText("Enjoy your walk.\n{$chips}"))->toBe("Enjoy your walk.\n");
});

it("withholds a bound glucose chart whose goal or target range is not a plain number in its data's unit", function (GlucoseUnit $unit, string $limits): void {
    $events = withGlucose($unit, fn (): array => composeReply(glucoseChart($limits)));

    expect(end($events))->toMatchArray(['accepted' => false, 'reason' => 'invalid_range']);
})->with([
    'a range typed in mmol/L over mg/dL readings' => [GlucoseUnit::MgDl, '"range":{"low":3.9,"high":10}'],
    'a goal read from local state' => [GlucoseUnit::MmolL, '"goal":{"$state":"/ui/goal"}'],
    'a range read from local state' => [GlucoseUnit::MmolL, '"range":{"$state":"/ui/target"}'],
]);

it("draws a bound glucose chart whose target range is in its data's unit", function (): void {
    $events = withGlucose(GlucoseUnit::MmolL, fn (): array => composeReply(glucoseChart('"range":{"low":3.9,"high":10}')));

    expect(end($events))->toMatchArray([
        'accepted' => true,
        'fallback_text' => "Glucose\n- Tue Nov 17, 2026: 7 mmol/L\n- Wed Nov 18, 2026: 6 mmol/L\nTarget range: 3.9–10 mmol/L\nEach day runs from midnight to midnight UTC.",
    ]);
});

it('binds a dataset only as the whole data of a chart', function (string $element): void {
    $events = withSteps(['2026-11-16' => 4200, '2026-11-17' => 5100], fn (): array => composeReply(implode("\n", [
        '```spec',
        '{"op":"add","path":"/root","value":"a"}',
        '{"op":"add","path":"/elements/a","value":'.$element.'}',
        '```',
    ])));

    expect(end($events))->toMatchArray(['type' => 'visual_end', 'accepted' => false, 'reason' => 'unresolved_reference']);
})->with([
    'one day read into a Metric' => '{"type":"Metric","props":{"label":"Steps on Monday","value":{"$state":"/data/step_count/0/value"},"unit":"steps"},"children":[]}',
    'one row bound as a chart' => '{"type":"BarChart","props":{"title":"Steps","data":{"$state":"/data/step_count/0"},"xKey":"day","yKey":"value","unit":"steps"},"children":[]}',
]);

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
 * @param  array<string, mixed>  $options
 * @return list<array<string, mixed>>
 */
function composeReply(string $reply, int $sliceLength = 7, array $options = []): array
{
    $composer = resolve(ReplyComposer::class, [...replyComposerOptions(), ...$options]);
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

/**
 * @template TResult
 *
 * @param  array<string, int|null>  $steps
 * @param  Closure(): TResult  $compose
 * @return TResult
 */
function withSteps(array $steps, Closure $compose): mixed
{
    $datasets = resolve(TurnDataRegistry::class);

    return $datasets->collect(function () use ($datasets, $steps, $compose): mixed {
        $datasets->register('stepCount', new VisualDataset(
            label: 'Steps',
            measure: 'daily total',
            unit: 'steps',
            rows: array_map(fn (string $day, ?int $value): array => ['day' => $day, 'value' => $value], array_keys($steps), $steps),
        ));

        return $compose();
    });
}

/**
 * @template TResult
 *
 * @param  Closure(): TResult  $compose
 * @return TResult
 */
function withGlucose(GlucoseUnit $unit, Closure $compose): mixed
{
    $datasets = resolve(TurnDataRegistry::class);
    $readings = $unit === GlucoseUnit::MgDl ? [126, 108] : [7, 6];

    return $datasets->collect(function () use ($datasets, $unit, $readings, $compose): mixed {
        $datasets->register('bloodGlucose', new VisualDataset(
            label: 'Blood Glucose',
            measure: 'daily average',
            unit: $unit->value,
            rows: [['day' => '2026-11-17', 'value' => $readings[0]], ['day' => '2026-11-18', 'value' => $readings[1]]],
            valueRange: $unit->validationRange(),
        ));

        return $compose();
    });
}

function glucoseChart(string $limits): string
{
    return implode("\n", [
        '```spec',
        '{"op":"add","path":"/root","value":"glucose"}',
        '{"op":"add","path":"/state/ui","value":{"goal":180,"target":{"low":70,"high":180}}}',
        '{"op":"add","path":"/elements/glucose","value":{"type":"LineChart","props":{"title":"Glucose","data":{"$state":"/data/blood_glucose"},"xKey":"day","yKey":"value","unit":"mmol/L",'.$limits.'},"children":[]}}',
        '```',
    ]);
}

function paragraphs(int $count): string
{
    $keys = array_map(fn (int $index): string => "text-{$index}", range(1, $count));
    $lines = [
        '{"op":"add","path":"/root","value":"texts"}',
        json_encode(['op' => 'add', 'path' => '/elements/texts', 'value' => ['type' => 'Stack', 'props' => (object) [], 'children' => $keys]]),
    ];

    foreach ($keys as $key) {
        $lines[] = json_encode(['op' => 'add', 'path' => "/elements/{$key}", 'value' => ['type' => 'Text', 'props' => ['text' => str_repeat('Walk a little more each day. ', 8)], 'children' => []]]);
    }

    return "```spec\n".implode("\n", $lines)."\n```\n";
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
