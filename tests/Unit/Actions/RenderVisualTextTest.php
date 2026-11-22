<?php

declare(strict_types=1);

use App\Actions\RenderVisualText;

covers(RenderVisualText::class);

it('renders every catalog component from its example', function (): void {
    $catalog = json_decode((string) file_get_contents(resource_path('js/components/chat/visuals/generated/acara-chat.json')), true, flags: JSON_THROW_ON_ERROR);
    $silentComponents = ['Separator', 'Segmented', 'Button', 'SuggestionChips'];

    foreach ($catalog['components'] as $type => $component) {
        $spec = [
            'root' => 'element',
            'elements' => [
                'element' => ['type' => $type, 'props' => $component['example'], 'children' => $component['acceptsChildren'] ? ['child'] : []],
                'child' => ['type' => 'Text', 'props' => ['text' => 'Inside'], 'children' => []],
            ],
        ];

        expect((new RenderVisualText)->handle($spec, 'en') === '')->toBe(in_array($type, $silentComponents, true), "{$type} text");
    }
});

it('tells a recorded zero apart from a missing day', function (string $locale, string $expected): void {
    $spec = [
        'root' => 'chart',
        'elements' => [
            'chart' => [
                'type' => 'BarChart',
                'props' => ['title' => 'Daily steps', 'data' => ['$state' => '/data/week'], 'xKey' => 'day', 'yKey' => 'steps', 'unit' => 'steps', 'goal' => 7000, 'dayBoundary' => 'Days use UTC.'],
                'children' => [],
            ],
        ],
        'state' => ['data' => ['week' => [
            ['day' => '2026-11-10', 'steps' => 4200],
            ['day' => '2026-11-11', 'steps' => 0],
            ['day' => '2026-11-12', 'steps' => null],
        ]]],
    ];

    expect((new RenderVisualText)->handle($spec, $locale))->toBe($expected);
})->with([
    'English' => ['en', "Daily steps\n- Tue Nov 10, 2026: 4,200 steps\n- Wed Nov 11, 2026: 0 steps\n- Thu Nov 12, 2026: no reading\nGoal: 7,000 steps\nDays use UTC."],
    'French' => ['fr', "Daily steps\n- mar. 10 nov. 2026 : 4\u{202F}200 pas\n- mer. 11 nov. 2026 : 0 pas\n- jeu. 12 nov. 2026 : aucune mesure\nObjectif : 7\u{202F}000 pas\nDays use UTC."],
    'Mongolian' => ['mn', "Daily steps\n- Мя 2026 11 сар 10: 4,200 алхам\n- Лх 2026 11 сар 11: 0 алхам\n- Пү 2026 11 сар 12: хэмжилт байхгүй\nЗорилт: 7,000 алхам\nDays use UTC."],
]);

it('sums up a series longer than a month instead of listing every day', function (string $locale, string $expected): void {
    $rows = array_map(fn (int $day): array => [
        'day' => now()->setDate(2026, 10, 1)->addDays($day)->toDateString(),
        'steps' => match ($day) {
            4 => 0,
            9, 20 => null,
            default => 5000 + $day * 10,
        },
    ], range(0, 31));
    $spec = [
        'root' => 'chart',
        'elements' => ['chart' => ['type' => 'LineChart', 'props' => ['title' => 'Daily steps', 'data' => $rows, 'xKey' => 'day', 'yKey' => 'steps', 'unit' => 'steps'], 'children' => []]],
    ];

    expect((new RenderVisualText)->handle($spec, $locale))->toBe($expected);
})->with([
    'English' => ['en', "Daily steps\nThu Oct 1, 2026 – Sun Nov 1, 2026\nAverage: 4,987.67 steps\nHighest: 5,310 steps (Sun Nov 1, 2026)\nLowest: 0 steps (Mon Oct 5, 2026)\nNo reading: 2 of 32"],
    'French' => ['fr', "Daily steps\njeu. 1 oct. 2026 – dim. 1 nov. 2026\nMoyenne : 4\u{202F}987,67 pas\nMaximum : 5\u{202F}310 pas (dim. 1 nov. 2026)\nMinimum : 0 pas (lun. 5 oct. 2026)\nAucune mesure : 2 sur 32"],
]);

it('names each switched view with the option that shows it', function (): void {
    $spec = [
        'root' => 'routine',
        'elements' => [
            'routine' => ['type' => 'Card', 'props' => ['title' => 'Wind-down routine'], 'children' => ['switch', 'weeknight', 'weekend']],
            'switch' => ['type' => 'Segmented', 'props' => ['options' => [['label' => 'Weeknights', 'value' => 'weeknight'], ['label' => 'Weekends', 'value' => 'weekend']], 'value' => ['$bindState' => '/ui/night']], 'children' => []],
            'weeknight' => ['type' => 'Steps', 'props' => ['items' => [['title' => 'Dim the lights']]], 'children' => [], 'visible' => ['$state' => '/ui/night', 'eq' => 'weeknight']],
            'weekend' => ['type' => 'Steps', 'props' => ['items' => [['title' => 'Take a warm shower']]], 'children' => [], 'visible' => ['$state' => '/ui/night', 'eq' => 'weekend']],
        ],
        'state' => ['ui' => ['night' => 'weeknight']],
    ];

    expect((new RenderVisualText)->handle($spec, 'en'))->toBe("Wind-down routine\nWeeknights\n1. Dim the lights\nWeekends\n1. Take a warm shower");
});

it('renders a nested visual in order with every switchable view', function (): void {
    $spec = [
        'root' => 'review',
        'elements' => [
            'review' => ['type' => 'Card', 'props' => ['title' => 'Week in review', 'subtitle' => 'November 10–16'], 'children' => ['intro', 'numbers', 'switch', 'glucose', 'list', 'missing']],
            'intro' => ['type' => 'Stack', 'props' => [], 'children' => ['heading', 'note']],
            'heading' => ['type' => 'Heading', 'props' => ['text' => 'Movement'], 'children' => []],
            'note' => ['type' => 'Text', 'props' => ['text' => ['$cond' => true, '$then' => 'Unsupported']], 'children' => []],
            'numbers' => ['type' => 'Grid', 'props' => ['columns' => 2], 'children' => ['average', 'goal']],
            'average' => ['type' => 'Metric', 'props' => ['label' => 'Daily average', 'value' => ['$state' => '/data/average'], 'unit' => 'steps'], 'children' => []],
            'goal' => ['type' => 'Progress', 'props' => ['label' => 'Daily movement', 'value' => 5400, 'goal' => 7000, 'unit' => 'steps', 'style' => 'ring'], 'children' => []],
            'switch' => ['type' => 'Segmented', 'props' => ['options' => [['label' => 'Steps', 'value' => 'steps'], ['label' => 'Glucose', 'value' => 'glucose']], 'value' => ['$bindState' => '/ui/metric']], 'children' => []],
            'glucose' => [
                'type' => 'LineChart',
                'props' => ['title' => 'Glucose', 'data' => ['$state' => '/data/glucose'], 'xKey' => 'at', 'yKey' => 'level', 'unit' => 'mmol/L', 'range' => ['low' => 3.9, 'high' => 10]],
                'visible' => ['$state' => '/ui/metric', 'eq' => 'glucose'],
                'children' => [],
            ],
            'list' => ['type' => 'List', 'props' => ['title' => 'Shopping'], 'children' => ['oats']],
            'oats' => ['type' => 'ListItem', 'props' => ['title' => 'Oats', 'trailing' => '500 g'], 'children' => ['kinds']],
            'kinds' => [
                'type' => 'Checklist',
                'props' => ['items' => [['id' => 'rolled', 'label' => 'Rolled oats'], ['id' => 'steel', 'label' => 'Steel-cut oats']], 'checked' => ['$bindState' => '/ui/checked']],
                'children' => [],
            ],
        ],
        'state' => [
            'data' => ['average' => 5400, 'glucose' => [['at' => '2026-11-12T13:05:00Z', 'level' => 6.2], ['at' => '2026-11-12T18:30:00Z', 'level' => null]]],
            'ui' => ['metric' => 'steps', 'checked' => ['rolled']],
        ],
    ];

    expect((new RenderVisualText)->handle($spec, 'en', 'America/Toronto'))->toBe(implode("\n", [
        'Week in review — November 10–16',
        'Movement',
        'Daily average: 5,400 steps',
        'Daily movement: 5,400 of 7,000 steps (77%)',
        'Glucose',
        '- Thu Nov 12, 2026 8:05 AM: 6.2 mmol/L',
        '- Thu Nov 12, 2026 1:30 PM: no reading',
        'Target range: 3.9–10 mmol/L',
        'Shopping',
        '- Oats (500 g)',
        '  - Rolled oats (done)',
        '  - Steel-cut oats',
    ]));
});
