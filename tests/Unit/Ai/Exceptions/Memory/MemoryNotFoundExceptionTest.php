<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryNotFoundException;

it('can be created with memory id and default message', function (): void {
    $exception = new MemoryNotFoundException('mem_123');

    expect($exception)
        ->memoryId->toBe('mem_123')
        ->getMessage()->toBe("Memory with ID 'mem_123' was not found.");
});

it('can be created with custom message', function (): void {
    $exception = new MemoryNotFoundException('mem_456', 'Custom error message');

    expect($exception)
        ->memoryId->toBe('mem_456')
        ->getMessage()->toBe('Custom error message');
});

it('extends Exception', function (): void {
    $exception = new MemoryNotFoundException('mem_789');

    expect($exception)->toBeInstanceOf(Exception::class);
});
