<?php

declare(strict_types=1);

use App\Actions\ValidateVisualSpec;
use App\Exceptions\InvalidVisualSpecException;
use App\Services\VisualCatalog;

covers(ValidateVisualSpec::class, VisualCatalog::class, InvalidVisualSpecException::class);

it('accepts every catalog example and rejects a link that is not https', function (): void {
    $catalog = json_decode((string) file_get_contents(resource_path('js/components/chat/visuals/generated/acara-chat.json')), flags: JSON_THROW_ON_ERROR);
    $validator = resolve(ValidateVisualSpec::class);

    foreach (get_object_vars($catalog->components) as $type => $component) {
        $element = (object) ['type' => $type, 'props' => $component->example, 'children' => $component->acceptsChildren ? ['child'] : []];

        if ($type === 'Button') {
            $element->on = (object) ['press' => (object) ['action' => 'sendMessage', 'params' => (object) ['text' => 'Plan my lunch']]];
        }

        $spec = (object) ['root' => 'element', 'elements' => (object) [
            'element' => $element,
            'child' => (object) ['type' => 'Text', 'props' => (object) ['text' => 'Inside'], 'children' => []],
        ]];

        expect($validator->handle($spec, 1)->elements->element->type)->toBe($type);
    }

    $insecureLink = json_decode('{"root":"chips","elements":{"chips":{"type":"SuggestionChips","props":{"items":[{"label":"Docs","action":{"action":"openLink","params":{"url":"http://example.com"}}}]},"children":[]}}}');

    expect(fn () => $validator->handle($insecureLink, 1))->toThrow(function (InvalidVisualSpecException $exception): void {
        expect($exception->reason)->toBe('invalid_props');
    });
});

it('drops unknown props, blank optional text and unreachable elements', function (): void {
    $spec = json_decode(<<<'JSON'
        {
            "root": "card",
            "elements": {
                "card": {"type": "Card", "props": {"title": "Review", "subtitle": "  ", "color": "red"}, "children": ["heading", "line"]},
                "heading": {"type": "Heading", "props": {"text": {"$state": "/ui/heading"}}, "children": [], "visible": {"$and": [{"$state": "/ui/open"}, {"$state": "/ui/metric", "eq": "steps"}]}},
                "line": {"type": "Separator", "props": {}, "children": []},
                "orphan": {"type": "Text", "props": {"text": "Never shown"}, "children": []}
            },
            "state": {"ui": {"heading": "Week", "open": true, "metric": "steps"}},
            "meta": {"draft": true}
        }
        JSON);

    expect(json_encode(resolve(ValidateVisualSpec::class)->handle($spec, 1), JSON_UNESCAPED_SLASHES))->toBe(
        '{"root":"card","elements":{"card":{"type":"Card","props":{"title":"Review"},"children":["heading","line"]},'
        .'"heading":{"type":"Heading","props":{"text":{"$state":"/ui/heading"}},"children":[],"visible":{"$and":[{"$state":"/ui/open"},{"$state":"/ui/metric","eq":"steps"}]}},'
        .'"line":{"type":"Separator","props":{},"children":[]}},"state":{"ui":{"heading":"Week","open":true,"metric":"steps"}}}',
    );
});

it('rejects a visual with a stable reason', function (string $spec, string $reason): void {
    expect(fn () => resolve(ValidateVisualSpec::class)->handle(json_decode($spec, flags: JSON_THROW_ON_ERROR), 1))
        ->toThrow(function (InvalidVisualSpecException $exception) use ($reason): void {
            expect($exception->reason)->toBe($reason);
        });
})->with([
    'missing root' => ['{"root":"a","elements":{}}', 'missing_root'],
    'unknown component' => ['{"root":"a","elements":{"a":{"type":"Chart","props":{},"children":[]}}}', 'unknown_component'],
    'invalid props' => ['{"root":"a","elements":{"a":{"type":"Progress","props":{"label":"Steps","value":10,"goal":0},"children":[]}}}', 'invalid_props'],
    'blank required text' => ['{"root":"a","elements":{"a":{"type":"Heading","props":{"text":"  "},"children":[]}}}', 'invalid_props'],
    'unresolved child' => ['{"root":"a","elements":{"a":{"type":"Card","props":{},"children":["b"]}}}', 'unresolved_child'],
    'children on a leaf' => ['{"root":"a","elements":{"a":{"type":"Text","props":{"text":"Hi"},"children":["b"]},"b":{"type":"Text","props":{"text":"Hi"},"children":[]}}}', 'children_not_allowed'],
    'shared child' => ['{"root":"a","elements":{"a":{"type":"Card","props":{},"children":["b","b"]},"b":{"type":"Text","props":{"text":"Hi"},"children":[]}}}', 'shared_child'],
    'state outside ui' => ['{"root":"a","elements":{"a":{"type":"Text","props":{"text":"Hi"},"children":[]}},"state":{"data":{"steps":5}}}', 'state_outside_ui'],
    'unresolved reference' => ['{"root":"a","elements":{"a":{"type":"Text","props":{"text":{"$state":"/ui/missing"}},"children":[]}},"state":{"ui":{}}}', 'unresolved_reference'],
    'unsupported expression' => ['{"root":"a","elements":{"a":{"type":"Text","props":{"text":{"$cond":true,"$then":"Hi"}},"children":[]}}}', 'unsupported_expression'],
    'unsupported field' => ['{"root":"a","elements":{"a":{"type":"Card","props":{},"children":[],"repeat":{"statePath":"/ui/items"}}}}', 'unsupported_field'],
    'button without an action' => ['{"root":"a","elements":{"a":{"type":"Button","props":{"label":"Go"},"children":[]}}}', 'missing_action'],
    'unknown action' => ['{"root":"a","elements":{"a":{"type":"Button","props":{"label":"Go"},"children":[],"on":{"press":{"action":"deleteRecords","params":{}}}}}}', 'unknown_action'],
    'state write outside ui' => ['{"root":"a","elements":{"a":{"type":"Button","props":{"label":"Go"},"children":[],"on":{"press":{"action":"setState","params":{"statePath":"/data/steps","value":1}}}}}}', 'invalid_action'],
    'uneven comparison' => ['{"root":"a","elements":{"a":{"type":"Comparison","props":{"columns":["Oats","Rice"],"rows":[{"label":"Fiber","values":[4,1,2]}]},"children":[]}}}', 'invalid_comparison'],
    'segment outside its options' => ['{"root":"a","elements":{"a":{"type":"Segmented","props":{"options":[{"label":"Steps","value":"steps"},{"label":"Sleep","value":"sleep"}],"value":"water"},"children":[]}}}', 'invalid_segmented'],
    'duplicate checklist ids' => ['{"root":"a","elements":{"a":{"type":"Checklist","props":{"items":[{"id":"oats","label":"Oats"},{"id":"oats","label":"More oats"}],"checked":[]},"children":[]}}}', 'invalid_checklist'],
    'chart row without a label' => ['{"root":"a","elements":{"a":{"type":"BarChart","props":{"title":"Steps","data":[{"day":3,"steps":10}],"xKey":"day","yKey":"steps","unit":"steps"},"children":[]}}}', 'invalid_chart_axis'],
    'inverted range' => ['{"root":"a","elements":{"a":{"type":"LineChart","props":{"title":"Sleep","data":[],"xKey":"day","yKey":"hours","unit":"h","range":{"low":9,"high":7}},"children":[]}}}', 'invalid_range'],
    'too many elements' => [fn (): string => visualWithTexts(129), 'too_many_elements'],
    'too deep' => [fn (): string => nestedCards(13), 'too_deep'],
    'too large' => [fn (): string => json_encode(['root' => 'a', 'elements' => ['a' => ['type' => 'Text', 'props' => ['text' => str_repeat('a', 140_000)], 'children' => []]]], JSON_THROW_ON_ERROR), 'too_large'],
]);

function visualWithTexts(int $count): string
{
    $keys = array_map(fn (int $index): string => "text-{$index}", range(1, $count - 1));
    $elements = ['list' => ['type' => 'Stack', 'props' => (object) [], 'children' => $keys]];

    foreach ($keys as $key) {
        $elements[$key] = ['type' => 'Text', 'props' => ['text' => 'Hi'], 'children' => []];
    }

    return json_encode(['root' => 'list', 'elements' => $elements], JSON_THROW_ON_ERROR);
}

function nestedCards(int $depth): string
{
    $elements = [];

    foreach (range(1, $depth) as $level) {
        $elements["card-{$level}"] = ['type' => 'Card', 'props' => (object) [], 'children' => $level < $depth ? ['card-'.($level + 1)] : []];
    }

    return json_encode(['root' => 'card-1', 'elements' => $elements], JSON_THROW_ON_ERROR);
}
