<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryStorageException;

it('can be created with message only', function (): void {
    $exception = new MemoryStorageException('Something went wrong');

    expect($exception)
        ->getMessage()->toBe('Something went wrong')
        ->operation->toBeNull()
        ->context->toBeNull();
});

it('can be created with all parameters', function (): void {
    $exception = new MemoryStorageException(
        message: 'Storage failed',
        operation: 'store',
        context: ['key' => 'value'],
    );

    expect($exception)
        ->getMessage()->toBe('Storage failed')
        ->operation->toBe('store')
        ->context->toBe(['key' => 'value']);
});

it('creates storeFailed exception with reason', function (): void {
    $exception = MemoryStorageException::storeFailed('Connection timeout');

    expect($exception)
        ->getMessage()->toBe('Failed to store memory: Connection timeout')
        ->operation->toBe('store')
        ->context->toBeNull();
});

it('creates storeFailed exception with context', function (): void {
    $exception = MemoryStorageException::storeFailed('Disk full', ['disk' => '/dev/sda1']);

    expect($exception)
        ->getMessage()->toBe('Failed to store memory: Disk full')
        ->operation->toBe('store')
        ->context->toBe(['disk' => '/dev/sda1']);
});

it('creates updateFailed exception', function (): void {
    $exception = MemoryStorageException::updateFailed('mem_123', 'Record locked');

    expect($exception)
        ->getMessage()->toBe("Failed to update memory 'mem_123': Record locked")
        ->operation->toBe('update')
        ->context->toBe(['memory_id' => 'mem_123']);
});

it('creates deleteFailed exception', function (): void {
    $exception = MemoryStorageException::deleteFailed('Permission denied');

    expect($exception)
        ->getMessage()->toBe('Failed to delete memory: Permission denied')
        ->operation->toBe('delete')
        ->context->toBeNull();
});

it('creates consolidationFailed exception', function (): void {
    $memoryIds = ['mem_1', 'mem_2', 'mem_3'];
    $exception = MemoryStorageException::consolidationFailed($memoryIds, 'Conflicting data');

    expect($exception)
        ->getMessage()->toBe('Failed to consolidate memories: Conflicting data')
        ->operation->toBe('consolidate')
        ->context->toBe(['memory_ids' => $memoryIds]);
});

it('extends Exception', function (): void {
    $exception = new MemoryStorageException('Test');

    expect($exception)->toBeInstanceOf(Exception::class);
});
