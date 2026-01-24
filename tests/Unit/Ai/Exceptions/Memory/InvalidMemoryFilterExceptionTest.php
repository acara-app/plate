<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\InvalidMemoryFilterException;

it('can be created with message only', function (): void {
    $exception = new InvalidMemoryFilterException('Invalid filter');

    expect($exception)
        ->getMessage()->toBe('Invalid filter')
        ->filter->toBe([])
        ->field->toBeNull();
});

it('can be created with all parameters', function (): void {
    $exception = new InvalidMemoryFilterException(
        message: 'Bad filter',
        filter: ['category' => 'invalid'],
        field: 'category',
    );

    expect($exception)
        ->getMessage()->toBe('Bad filter')
        ->filter->toBe(['category' => 'invalid'])
        ->field->toBe('category');
});

it('creates emptyFilter exception', function (): void {
    $exception = InvalidMemoryFilterException::emptyFilter();

    expect($exception)
        ->getMessage()->toBe('A non-empty filter is required for this operation.')
        ->filter->toBe([])
        ->field->toBeNull();
});

it('creates invalidField exception', function (): void {
    $exception = InvalidMemoryFilterException::invalidField('foo', ['category', 'importance', 'source']);

    expect($exception)
        ->getMessage()->toBe("Invalid filter field 'foo'. Allowed fields: category, importance, source")
        ->field->toBe('foo');
});

it('creates invalidValue exception with string value', function (): void {
    $exception = InvalidMemoryFilterException::invalidValue('importance', 'high', 'int');

    expect($exception)
        ->getMessage()->toBe("Invalid value for filter field 'importance'. Expected int, got string.")
        ->field->toBe('importance');
});

it('creates invalidValue exception with array value', function (): void {
    $exception = InvalidMemoryFilterException::invalidValue('category', ['a', 'b'], 'string');

    expect($exception)
        ->getMessage()->toBe("Invalid value for filter field 'category'. Expected string, got array.")
        ->field->toBe('category');
});

it('creates invalidValue exception with null value', function (): void {
    $exception = InvalidMemoryFilterException::invalidValue('source', null, 'string');

    expect($exception)
        ->getMessage()->toBe("Invalid value for filter field 'source'. Expected string, got null.")
        ->field->toBe('source');
});

it('extends Exception', function (): void {
    $exception = new InvalidMemoryFilterException('Test');

    expect($exception)->toBeInstanceOf(Exception::class);
});
