<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidVisualSpecException;
use App\Services\VisualCatalog;
use Opis\JsonSchema\Validator;
use stdClass;

final readonly class ValidateVisualSpec
{
    private const string LOCAL_STATE_PREFIX = '/ui/';

    /**
     * @var list<string>
     */
    private const array ELEMENT_FIELDS = ['type', 'props', 'children', 'visible', 'on'];

    /**
     * @var list<string>
     */
    private const array CONDITION_OPERATORS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'not'];

    private Validator $schemaValidator;

    public function __construct(private VisualCatalog $catalog)
    {
        $this->schemaValidator = new Validator;
    }

    /**
     * @throws InvalidVisualSpecException
     */
    public function handle(stdClass $spec, int $catalogVersion): stdClass
    {
        $limits = $this->catalog->limits();
        $root = $spec->root ?? null;
        $elements = $spec->elements ?? null;
        $state = $spec->state ?? new stdClass;

        throw_if(! is_string($root) || ! $elements instanceof stdClass || ! property_exists($elements, $root), InvalidVisualSpecException::class, 'missing_root');

        throw_if(! $state instanceof stdClass
        || array_diff(array_keys(get_object_vars($state)), ['ui']) !== []
        || (property_exists($state, 'ui') && ! $state->ui instanceof stdClass), InvalidVisualSpecException::class, 'state_outside_ui');

        throw_if(count(get_object_vars($elements)) > $limits['elements'], InvalidVisualSpecException::class, 'too_many_elements');

        $sanitizedElements = new stdClass;

        foreach ($this->reachableKeys($root, $elements, $limits['depth']) as $key) {
            $sanitizedElements->{$key} = $this->element($elements->{$key}, $state, $catalogVersion);
        }

        $sanitized = new stdClass;
        $sanitized->root = $root;
        $sanitized->elements = $sanitizedElements;

        if (property_exists($state, 'ui')) {
            $sanitized->state = $state;
        }

        throw_if(mb_strlen((string) json_encode($sanitized)) > $limits['bytes'], InvalidVisualSpecException::class, 'too_large');

        return $sanitized;
    }

    /**
     * @return list<string>
     */
    private function reachableKeys(string $root, stdClass $elements, int $maxDepth): array
    {
        $keys = [];
        $depths = [$root => 1];
        $queue = [$root];

        while (($key = array_shift($queue)) !== null) {
            $depth = $depths[$key];

            throw_if($depth > $maxDepth, InvalidVisualSpecException::class, 'too_deep');

            $keys[] = $key;

            foreach ($this->childKeys($elements->{$key}) as $child) {
                throw_unless(property_exists($elements, $child), InvalidVisualSpecException::class, 'unresolved_child');

                throw_if(isset($depths[$child]), InvalidVisualSpecException::class, 'shared_child');

                $depths[$child] = $depth + 1;
                $queue[] = $child;
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function childKeys(mixed $element): array
    {
        $children = $element instanceof stdClass ? $element->children ?? [] : [];

        throw_if(! is_array($children) || ! array_is_list($children) || array_filter($children, is_string(...)) !== $children, InvalidVisualSpecException::class, 'unresolved_child');

        return $children;
    }

    private function element(mixed $element, stdClass $state, int $catalogVersion): stdClass
    {
        throw_unless($element instanceof stdClass, InvalidVisualSpecException::class, 'unknown_component');

        throw_if(array_diff(array_keys(get_object_vars($element)), self::ELEMENT_FIELDS) !== [], InvalidVisualSpecException::class, 'unsupported_field');

        $type = $element->type ?? null;
        $component = is_string($type) ? $this->catalog->component($type, $catalogVersion) : null;

        throw_if($type === null || $component === null, InvalidVisualSpecException::class, 'unknown_component');

        $children = $this->childKeys($element);

        throw_if($children !== [] && ! $component['acceptsChildren'], InvalidVisualSpecException::class, 'children_not_allowed');

        $props = $this->props($element->props ?? new stdClass, $component['props']);
        $resolved = $this->resolve($props, $state);
        $this->assertMatches($resolved, $component['props'], 'invalid_props');
        $this->assertComponentRules($type, $resolved);

        $sanitized = new stdClass;
        $sanitized->type = $type;
        $sanitized->props = $props;
        $sanitized->children = $children;

        if (property_exists($element, 'visible')) {
            $this->assertCondition($element->visible);
            $sanitized->visible = $element->visible;
        }

        $on = $this->events($type, $element->on ?? null, $state, $catalogVersion);

        if ($on instanceof stdClass) {
            $sanitized->on = $on;
        }

        return $sanitized;
    }

    private function props(mixed $props, stdClass $schema): stdClass
    {
        throw_unless($props instanceof stdClass, InvalidVisualSpecException::class, 'invalid_props');

        $known = $schema->properties ?? new stdClass;
        $required = is_array($schema->required ?? null) ? $schema->required : [];
        $sanitized = new stdClass;

        foreach (get_object_vars($props) as $name => $value) {
            $isBlank = is_string($value) && mb_trim($value) === '';
            if (! $known instanceof stdClass) {
                continue;
            }
            if (! property_exists($known, $name)) {
                continue;
            }
            if ($isBlank && ! in_array($name, $required, true)) {
                continue;
            }

            $sanitized->{$name} = $value;
        }

        return $sanitized;
    }

    private function resolve(mixed $value, stdClass $state): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->resolve($item, $state), $value);
        }

        if (! $value instanceof stdClass) {
            return $value;
        }

        $fields = get_object_vars($value);
        $pointer = $fields['$state'] ?? $fields['$bindState'] ?? null;

        if ($pointer !== null) {
            throw_if(count($fields) !== 1 || ! is_string($pointer) || ! str_starts_with($pointer, self::LOCAL_STATE_PREFIX), InvalidVisualSpecException::class, 'unresolved_reference');

            return $this->stateValue($state, $pointer);
        }

        $resolved = new stdClass;

        foreach ($fields as $name => $item) {
            throw_if(str_starts_with((string) $name, '$'), InvalidVisualSpecException::class, 'unsupported_expression');

            $resolved->{$name} = $this->resolve($item, $state);
        }

        return $resolved;
    }

    private function stateValue(stdClass $state, string $pointer): mixed
    {
        $value = $state;

        foreach (explode('/', mb_substr($pointer, 1)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if ($value instanceof stdClass && property_exists($value, $segment)) {
                $value = $value->{$segment};
            } elseif (is_array($value) && ctype_digit($segment) && array_key_exists((int) $segment, $value)) {
                $value = $value[(int) $segment];
            } else {
                throw new InvalidVisualSpecException('unresolved_reference');
            }
        }

        return $value;
    }

    private function assertMatches(mixed $data, stdClass $schema, string $reason): void
    {
        throw_unless($this->schemaValidator->validate($data, $schema)->isValid(), InvalidVisualSpecException::class, $reason);
    }

    private function assertComponentRules(string $type, mixed $props): void
    {
        if (! $props instanceof stdClass) {
            return;
        }

        $reason = match ($type) {
            'Comparison' => $this->hasEvenRows($props) ? null : 'invalid_comparison',
            'Segmented' => $this->hasSelectableOptions($props) ? null : 'invalid_segmented',
            'Checklist' => $this->hasUniqueIds($props->items ?? []) ? null : 'invalid_checklist',
            'BarChart', 'LineChart' => match (true) {
                ! $this->hasChartAxis($props) => 'invalid_chart_axis',
                ! $this->hasOrderedRange($props) => 'invalid_range',
                default => null,
            },
            default => null,
        };

        throw_if($reason !== null, InvalidVisualSpecException::class, $reason);
    }

    private function hasEvenRows(stdClass $props): bool
    {
        $columns = is_array($props->columns ?? null) ? count($props->columns) : 0;

        foreach (is_array($props->rows ?? null) ? $props->rows : [] as $row) {
            $values = $row instanceof stdClass ? $row->values ?? null : null;

            if (! is_array($values) || count($values) !== $columns) {
                return false;
            }
        }

        return true;
    }

    private function hasSelectableOptions(stdClass $props): bool
    {
        $values = array_map(
            fn (mixed $option): mixed => $option instanceof stdClass ? $option->value ?? null : null,
            is_array($props->options ?? null) ? $props->options : [],
        );

        return count($values) === count(array_unique($values, SORT_REGULAR)) && in_array($props->value ?? null, $values, true);
    }

    private function hasUniqueIds(mixed $items): bool
    {
        $ids = array_map(
            fn (mixed $item): mixed => $item instanceof stdClass ? $item->id ?? null : null,
            is_array($items) ? $items : [],
        );

        return count($ids) === count(array_unique($ids, SORT_REGULAR));
    }

    private function hasChartAxis(stdClass $props): bool
    {
        $xKey = $props->xKey ?? null;

        foreach (is_array($props->data ?? null) ? $props->data : [] as $row) {
            $label = is_string($xKey) && $row instanceof stdClass ? $row->{$xKey} ?? null : null;

            if (! is_string($label) || mb_trim($label) === '') {
                return false;
            }
        }

        return true;
    }

    private function hasOrderedRange(stdClass $props): bool
    {
        $range = $props->range ?? null;

        return ! $range instanceof stdClass || $range->low <= $range->high;
    }

    private function assertCondition(mixed $condition): void
    {
        if (is_bool($condition)) {
            return;
        }

        if (is_array($condition)) {
            array_walk($condition, fn (mixed $part) => $this->assertCondition($part));

            return;
        }

        $fields = $condition instanceof stdClass ? get_object_vars($condition) : [];
        $group = $fields['$and'] ?? $fields['$or'] ?? null;

        if (count($fields) === 1 && is_array($group)) {
            $this->assertCondition($group);

            return;
        }

        $path = $fields['$state'] ?? null;
        unset($fields['$state']);

        throw_if(! is_string($path) || ! str_starts_with($path, self::LOCAL_STATE_PREFIX) || array_diff(array_keys($fields), self::CONDITION_OPERATORS) !== [], InvalidVisualSpecException::class, 'unsupported_expression');
    }

    private function events(string $type, mixed $on, stdClass $state, int $catalogVersion): ?stdClass
    {
        if ($type !== 'Button') {
            throw_if($on !== null, InvalidVisualSpecException::class, 'unsupported_field');

            return null;
        }

        throw_if(! $on instanceof stdClass || ($on->press ?? null) === null || array_diff(array_keys(get_object_vars($on)), ['press']) !== [], InvalidVisualSpecException::class, 'missing_action');

        foreach (is_array($on->press) ? $on->press : [$on->press] as $binding) {
            $action = $binding instanceof stdClass ? $binding->action ?? null : null;
            $schema = is_string($action) ? $this->catalog->actionParams($action, $catalogVersion) : null;

            throw_if(! $binding instanceof stdClass || ! $schema instanceof stdClass, InvalidVisualSpecException::class, 'unknown_action');

            $this->assertMatches($this->resolve($binding->params ?? new stdClass, $state), $schema, 'invalid_action');
        }

        return $on;
    }
}
