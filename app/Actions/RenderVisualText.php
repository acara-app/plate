<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Number;
use Throwable;

/**
 * @phpstan-type TVisual array{elements: array<array-key, mixed>, state: array<array-key, mixed>, variants: array<string, array<string, string>>, locale: string, timezone: string}
 */
final readonly class RenderVisualText
{
    private const int MAX_DEPTH = 12;

    private const int LISTED_POINTS = 31;

    /**
     * @param  array<array-key, mixed>  $spec
     */
    public function handle(array $spec, string $locale, string $timezone = 'UTC'): string
    {
        $root = $spec['root'] ?? null;
        $elements = $spec['elements'] ?? null;
        $state = $spec['state'] ?? [];

        if (! is_string($root) || ! is_array($elements)) {
            return '';
        }

        $visual = [
            'elements' => $elements,
            'state' => is_array($state) ? $state : [],
            'variants' => $this->variants($elements),
            'locale' => $locale,
            'timezone' => $timezone,
        ];

        return implode("\n", $this->element($root, $visual, []));
    }

    /**
     * @param  TVisual  $visual
     * @param  list<string>  $ancestors
     * @return list<string>
     */
    private function element(string $key, array $visual, array $ancestors): array
    {
        $element = $visual['elements'][$key] ?? null;

        if (! is_array($element) || in_array($key, $ancestors, true) || count($ancestors) >= self::MAX_DEPTH) {
            return [];
        }

        $resolved = $this->resolve($element['props'] ?? [], $visual['state']);
        $props = is_array($resolved) ? $resolved : [];
        $children = fn (): array => $this->children($element, $visual, [...$ancestors, $key]);

        return $this->named($this->variantLabel($element['visible'] ?? null, $visual), match ($element['type'] ?? null) {
            'Card' => [...$this->lines($this->joined([$this->text($props, 'title'), $this->text($props, 'subtitle')])), ...$children()],
            'Stack', 'Grid' => $children(),
            'List', 'Disclosure' => [...$this->lines($this->text($props, 'title')), ...$children()],
            'ListItem' => [...$this->lines($this->listItem($props)), ...$this->indented($children())],
            'Heading', 'Text', 'Badge' => $this->lines($this->text($props, 'text')),
            'Callout' => $this->lines($this->labelled($this->text($props, 'title'), $this->text($props, 'message'), $visual)),
            'Metric' => $this->lines($this->metric($props, $visual)),
            'Progress' => $this->lines($this->progress($props, $visual)),
            'Steps' => $this->steps($props),
            'Comparison' => $this->comparison($props, $visual),
            'BarChart', 'LineChart' => $this->chart($props, $visual),
            'MacroBreakdown' => $this->lines($this->macros($props, $visual)),
            'RiskMeter' => $this->lines($this->risk($props, $visual)),
            'Checklist' => $this->checklist($props, $visual),
            default => [],
        });
    }

    /**
     * @param  array<array-key, mixed>  $elements
     * @return array<string, array<string, string>>
     */
    private function variants(array $elements): array
    {
        $variants = [];

        foreach ($elements as $element) {
            $props = is_array($element) && ($element['type'] ?? null) === 'Segmented' && is_array($element['props'] ?? null) ? $element['props'] : [];
            $binding = $props['value'] ?? null;
            $path = is_array($binding) ? $binding['$bindState'] ?? null : null;
            $options = $props['options'] ?? null;
            if (! is_string($path)) {
                continue;
            }

            if (! is_array($options)) {
                continue;
            }

            foreach ($options as $option) {
                if (is_array($option) && is_string($option['value'] ?? null) && is_string($option['label'] ?? null)) {
                    $variants[$path][$option['value']] = $option['label'];
                }
            }
        }

        return $variants;
    }

    /**
     * @param  TVisual  $visual
     */
    private function variantLabel(mixed $condition, array $visual): ?string
    {
        if (! is_array($condition) || array_keys($condition) !== ['$state', 'eq'] || ! is_string($condition['$state']) || ! is_string($condition['eq'])) {
            return null;
        }

        return $visual['variants'][$condition['$state']][$condition['eq']] ?? null;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function named(?string $label, array $lines): array
    {
        return $label === null || $lines === [] || $lines[0] === $label ? $lines : [$label, ...$lines];
    }

    /**
     * @param  array<array-key, mixed>  $element
     * @param  TVisual  $visual
     * @param  list<string>  $ancestors
     * @return list<string>
     */
    private function children(array $element, array $visual, array $ancestors): array
    {
        $children = $element['children'] ?? [];
        $lines = [];

        if (! is_array($children)) {
            return [];
        }

        foreach ($children as $child) {
            if (is_string($child)) {
                array_push($lines, ...$this->element($child, $visual, $ancestors));
            }
        }

        return $lines;
    }

    /**
     * @param  array<array-key, mixed>  $state
     */
    private function resolve(mixed $value, array $state): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $pointer = $value['$state'] ?? $value['$bindState'] ?? null;

        if (is_string($pointer)) {
            return $this->pointer($state, $pointer);
        }

        foreach (array_keys($value) as $key) {
            if (is_string($key) && str_starts_with($key, '$')) {
                return null;
            }
        }

        return array_map(fn (mixed $item): mixed => $this->resolve($item, $state), $value);
    }

    /**
     * @param  array<array-key, mixed>  $state
     */
    private function pointer(array $state, string $pointer): mixed
    {
        if (! str_starts_with($pointer, '/')) {
            return null;
        }

        $value = $state;

        foreach (explode('/', mb_substr($pointer, 1)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $props
     */
    private function listItem(array $props): ?string
    {
        $title = $this->joined([$this->text($props, 'title'), $this->text($props, 'subtitle')]);

        if ($title === null) {
            return null;
        }

        $trailing = $this->text($props, 'trailing');

        return $trailing === null ? "- {$title}" : "- {$title} ({$trailing})";
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     */
    private function metric(array $props, array $visual): ?string
    {
        $line = $this->labelled(
            $this->text($props, 'label'),
            $this->quantity($props['value'] ?? null, $this->text($props, 'unit'), $visual),
            $visual,
        );
        $trend = $props['trend'] ?? null;

        if ($line !== null && in_array($trend, ['up', 'down', 'neutral'], true)) {
            $line = "{$line} ({$this->trans("trend.{$trend}", $visual)})";
        }

        return $this->withDetail($line, $this->text($props, 'detail'));
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     */
    private function progress(array $props, array $visual): ?string
    {
        $label = $this->text($props, 'label');
        $value = $props['value'] ?? null;
        $goal = $props['goal'] ?? null;

        if ((! is_int($value) && ! is_float($value)) || (! is_int($goal) && ! is_float($goal)) || $goal <= 0) {
            return $label;
        }

        return $this->labelled($label, $this->trans('progress', $visual, [
            'value' => $this->number($value, $visual),
            'goal' => (string) $this->quantity($goal, $this->text($props, 'unit'), $visual),
            'percent' => (string) round($value / $goal * 100),
        ]), $visual);
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @return list<string>
     */
    private function steps(array $props): array
    {
        $lines = [];

        foreach ($this->rows($props, 'items') as $item) {
            $title = $this->text($item, 'title');

            if ($title === null) {
                continue;
            }

            $duration = $this->text($item, 'duration');
            $step = count($lines) + 1;
            $line = $duration === null ? "{$step}. {$title}" : "{$step}. {$title} ({$duration})";
            $lines[] = (string) $this->withDetail($line, $this->text($item, 'detail'));
        }

        return $lines;
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     * @return list<string>
     */
    private function comparison(array $props, array $visual): array
    {
        $columns = $props['columns'] ?? [];
        $columns = is_array($columns) ? array_values($columns) : [];

        $lines = [];

        foreach ($this->rows($props, 'rows') as $row) {
            $values = $row['values'] ?? [];
            $cells = [];

            foreach (is_array($values) ? array_values($values) : [] as $index => $value) {
                $reading = $this->quantity($value, null, $visual);
                $column = $columns[$index] ?? null;

                if ($reading !== null) {
                    $cells[] = is_string($column) ? "{$column} {$reading}" : $reading;
                }
            }

            $line = $this->labelled($this->text($row, 'label'), $cells === [] ? null : implode('; ', $cells), $visual);
            array_push($lines, ...$this->lines($line === null ? null : "- {$line}"));
        }

        $winner = $props['winner'] ?? null;
        $option = is_int($winner) ? $columns[$winner] ?? null : null;

        if (is_string($option)) {
            $lines[] = $this->trans('better_choice', $visual, ['option' => $option]);
        }

        return $lines;
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     * @return list<string>
     */
    private function chart(array $props, array $visual): array
    {
        $xKey = $this->text($props, 'xKey');
        $yKey = $this->text($props, 'yKey');
        $unit = $this->text($props, 'unit');
        $rows = $this->rows($props, 'data');
        $points = array_map(fn (array $row): array => [
            'label' => $xKey === null ? null : $this->axis($row[$xKey] ?? null, $visual),
            'value' => $yKey === null ? null : $row[$yKey] ?? null,
        ], $rows);
        $lines = [
            ...$this->lines($this->text($props, 'title')),
            ...(count($points) > self::LISTED_POINTS ? $this->seriesSummary($points, $unit, $visual) : $this->seriesPoints($points, $unit, $visual)),
        ];

        $goal = $props['goal'] ?? null;

        if (is_int($goal) || is_float($goal)) {
            $lines[] = $this->trans('goal', $visual, ['value' => (string) $this->quantity($goal, $unit, $visual)]);
        }

        $range = $props['range'] ?? null;
        $low = is_array($range) ? $range['low'] ?? null : null;
        $high = is_array($range) ? $range['high'] ?? null : null;

        if ((is_int($low) || is_float($low)) && (is_int($high) || is_float($high))) {
            $lines[] = $this->trans('target_range', $visual, [
                'low' => $this->number($low, $visual),
                'high' => (string) $this->quantity($high, $unit, $visual),
            ]);
        }

        return [...$lines, ...$this->lines($this->text($props, 'dayBoundary'))];
    }

    /**
     * @param  list<array{label: string|null, value: mixed}>  $points
     * @param  TVisual  $visual
     * @return list<string>
     */
    private function seriesPoints(array $points, ?string $unit, array $visual): array
    {
        return array_map(
            fn (array $point): string => "- {$this->labelled($point['label'], $this->quantity($point['value'], $unit, $visual) ?? $this->trans('missing_reading', $visual), $visual)}",
            $points,
        );
    }

    /**
     * @param  list<array{label: string|null, value: mixed}>  $points
     * @param  TVisual  $visual
     * @return list<string>
     */
    private function seriesSummary(array $points, ?string $unit, array $visual): array
    {
        $readings = array_values(array_filter($points, fn (array $point): bool => is_int($point['value']) || is_float($point['value'])));
        $missing = count($points) - count($readings);
        $from = $points[0]['label'];
        $to = $points[count($points) - 1]['label'];
        $lines = $from === null || $to === null ? [] : [$this->trans('span', $visual, ['from' => $from, 'to' => $to])];

        if ($readings !== []) {
            $values = array_column($readings, 'value');
            $highest = $readings[array_search(max($values), $values, true)];
            $lowest = $readings[array_search(min($values), $values, true)];
            $lines[] = (string) $this->labelled($this->trans('average', $visual), $this->quantity(array_sum($values) / count($values), $unit, $visual), $visual);
            $lines[] = (string) $this->labelled($this->trans('highest', $visual), $this->reading($highest, $unit, $visual), $visual);
            $lines[] = (string) $this->labelled($this->trans('lowest', $visual), $this->reading($lowest, $unit, $visual), $visual);
        }

        if ($missing > 0) {
            $lines[] = $this->trans('missing_count', $visual, ['count' => (string) $missing, 'total' => (string) count($points)]);
        }

        return $lines;
    }

    /**
     * @param  array{label: string|null, value: mixed}  $point
     * @param  TVisual  $visual
     */
    private function reading(array $point, ?string $unit, array $visual): ?string
    {
        $quantity = $this->quantity($point['value'], $unit, $visual);

        return $quantity === null || $point['label'] === null ? $quantity : "{$quantity} ({$point['label']})";
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     */
    private function macros(array $props, array $visual): ?string
    {
        $nutrients = [];

        foreach (['carbs', 'protein', 'fat', 'fiber'] as $nutrient) {
            $grams = $this->quantity($props[$nutrient] ?? null, 'g', $visual);

            if ($grams !== null) {
                $nutrients[] = $this->trans('nutrient', $visual, [
                    'nutrient' => $this->trans("nutrients.{$nutrient}", $visual),
                    'value' => $grams,
                ]);
            }
        }

        return $this->labelled(
            $this->quantity($props['calories'] ?? null, 'kcal', $visual),
            $nutrients === [] ? null : implode(', ', $nutrients),
            $visual,
        );
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     */
    private function risk(array $props, array $visual): ?string
    {
        $level = $props['level'] ?? null;
        $verdict = in_array($level, ['low', 'medium', 'high'], true) ? $this->trans("risk.{$level}", $visual) : null;

        return $this->withDetail($this->labelled($this->text($props, 'label'), $verdict, $visual), $this->text($props, 'detail'));
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @param  TVisual  $visual
     * @return list<string>
     */
    private function checklist(array $props, array $visual): array
    {
        $checked = $props['checked'] ?? [];
        $lines = [];

        foreach ($this->rows($props, 'items') as $item) {
            $label = $this->text($item, 'label');

            if ($label === null) {
                continue;
            }

            $done = is_array($checked) && in_array($item['id'] ?? null, $checked, true);
            $lines[] = $done ? "- {$this->trans('done', $visual, ['item' => $label])}" : "- {$label}";
        }

        return $lines;
    }

    /**
     * @param  TVisual  $visual
     */
    private function axis(mixed $value, array $visual): ?string
    {
        if (is_int($value) || is_float($value)) {
            return $this->number($value, $visual);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                return CarbonImmutable::parse($value, 'UTC')->settings(['locale' => $visual['locale']])->isoFormat('ddd ll');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value) === 1) {
                return CarbonImmutable::parse($value)
                    ->setTimezone($visual['timezone'])
                    ->settings(['locale' => $visual['locale']])
                    ->isoFormat('ddd lll');
            }
        } catch (Throwable) {
            return $value;
        }

        return $value;
    }

    /**
     * @param  TVisual  $visual
     */
    private function quantity(mixed $value, ?string $unit, array $visual): ?string
    {
        if (is_string($value)) {
            return mb_trim($value) === '' ? null : $value;
        }

        if (! is_int($value) && ! is_float($value)) {
            return null;
        }

        $number = $this->number($value, $visual);

        return $unit === null ? $number : "{$number} {$this->unit($unit, $visual)}";
    }

    /**
     * @param  TVisual  $visual
     */
    private function number(int|float $value, array $visual): string
    {
        $formatted = Number::format($value, maxPrecision: 2, locale: $visual['locale']);

        return is_string($formatted) ? $formatted : (string) $value;
    }

    /**
     * @param  TVisual  $visual
     */
    private function unit(string $unit, array $visual): string
    {
        return Lang::has("visuals.units.{$unit}", $visual['locale']) ? $this->trans("units.{$unit}", $visual) : $unit;
    }

    /**
     * @param  TVisual  $visual
     */
    private function labelled(?string $label, ?string $value, array $visual): ?string
    {
        if ($label === null || $value === null) {
            return $label ?? $value;
        }

        return $this->trans('point', $visual, ['label' => $label, 'value' => $value]);
    }

    private function withDetail(?string $line, ?string $detail): ?string
    {
        if ($line === null || $detail === null) {
            return $line ?? $detail;
        }

        return "{$line} — {$detail}";
    }

    /**
     * @param  TVisual  $visual
     * @param  array<string, string>  $replace
     */
    private function trans(string $key, array $visual, array $replace = []): string
    {
        $line = __("visuals.{$key}", $replace, $visual['locale']);

        return is_string($line) ? $line : $key;
    }

    /**
     * @param  array<array-key, mixed>  $props
     */
    private function text(array $props, string $key): ?string
    {
        $value = $props[$key] ?? null;

        return is_string($value) && mb_trim($value) !== '' ? mb_trim($value) : null;
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function joined(array $parts): ?string
    {
        $parts = array_filter($parts, fn (?string $part): bool => $part !== null);

        return $parts === [] ? null : implode(' — ', $parts);
    }

    /**
     * @param  array<array-key, mixed>  $props
     * @return list<array<array-key, mixed>>
     */
    private function rows(array $props, string $key): array
    {
        $rows = $props[$key] ?? [];

        return is_array($rows) ? array_values(array_filter($rows, is_array(...))) : [];
    }

    /**
     * @return list<string>
     */
    private function lines(?string $line): array
    {
        return $line === null || $line === '' ? [] : [$line];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function indented(array $lines): array
    {
        return array_map(fn (string $line): string => "  {$line}", $lines);
    }
}
