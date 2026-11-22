<?php

declare(strict_types=1);

namespace App\Actions;

use InvalidArgumentException;
use stdClass;

final readonly class ApplyJsonPatch
{
    public function handle(mixed $document, stdClass $operation): mixed
    {
        $path = $operation->path ?? null;

        throw_if(! is_string($path) || ($path !== '' && ! str_starts_with($path, '/')), InvalidArgumentException::class, 'A patch path must be a JSON Pointer.');

        $segments = $path === '' ? [] : array_map(
            fn (string $segment): string => str_replace(['~1', '~0'], ['/', '~'], $segment),
            explode('/', mb_substr($path, 1)),
        );

        return match ($operation->op ?? null) {
            'add' => $this->write($document, $segments, $this->value($operation), insert: true),
            'replace' => $this->write($document, $segments, $this->value($operation), insert: false),
            'remove' => $this->remove($document, $segments),
            default => throw new InvalidArgumentException('Only add, replace and remove patches are supported.'),
        };
    }

    private function value(stdClass $operation): mixed
    {
        throw_unless(property_exists($operation, 'value'), InvalidArgumentException::class, 'An add or replace patch needs a value.');

        return $operation->value;
    }

    /**
     * @param  list<string>  $segments
     */
    private function write(mixed $node, array $segments, mixed $value, bool $insert): mixed
    {
        if ($segments === []) {
            return $value;
        }

        $segment = array_shift($segments);

        if (is_array($node)) {
            $index = $this->index($node, $segment, $insert && $segments === []);

            if ($segments === []) {
                if ($insert) {
                    array_splice($node, $index, 0, [$value]);
                } else {
                    $node[$index] = $value;
                }

                return $node;
            }

            $node[$index] = $this->write($this->container($node[$index] ?? null, $segments), $segments, $value, $insert);

            return $node;
        }

        $object = $node instanceof stdClass ? $node : new stdClass;
        $object->{$segment} = $segments === []
            ? $value
            : $this->write($this->container($object->{$segment} ?? null, $segments), $segments, $value, $insert);

        return $object;
    }

    /**
     * @param  list<string>  $segments
     */
    private function remove(mixed $node, array $segments): mixed
    {
        $segment = array_shift($segments);

        throw_if($segment === null, InvalidArgumentException::class, 'The whole document cannot be removed.');

        if (is_array($node)) {
            $index = $this->index($node, $segment, false);

            if ($segments === []) {
                array_splice($node, $index, 1);

                return $node;
            }

            $node[$index] = $this->remove($node[$index], $segments);

            return $node;
        }

        if (! $node instanceof stdClass || ! property_exists($node, $segment)) {
            return $node;
        }

        if ($segments === []) {
            unset($node->{$segment});

            return $node;
        }

        $node->{$segment} = $this->remove($node->{$segment}, $segments);

        return $node;
    }

    /**
     * @param  array<array-key, mixed>  $list
     */
    private function index(array $list, string $segment, bool $appendable): int
    {
        $count = count($list);
        $index = $segment === '-' ? $count : (ctype_digit($segment) ? (int) $segment : -1);
        $limit = $appendable ? $count : $count - 1;

        throw_if($index < 0 || $index > $limit, InvalidArgumentException::class, "The array index {$segment} is out of range.");

        return $index;
    }

    /**
     * @param  list<string>  $remaining
     */
    private function container(mixed $existing, array $remaining): mixed
    {
        if ($existing instanceof stdClass || is_array($existing)) {
            return $existing;
        }

        return $remaining !== [] && ($remaining[0] === '-' || ctype_digit($remaining[0])) ? [] : new stdClass;
    }
}
