<?php

declare(strict_types=1);

use App\Ai\Contracts\Memory\StoreMemoryTool;
use App\Ai\Facades\Memory;

it('throws exception for unknown method', function (): void {
    Memory::unknownMethod('test');
})->throws(BadMethodCallException::class, 'Method Memory::unknownMethod() does not exist.');

it('resolves and invokes the correct tool', function (): void {
    // Create a fake tool using anonymous class
    $fakeTool = new class implements StoreMemoryTool
    {
        public function __invoke(
            string $content,
            array $metadata = [],
            ?array $vector = null,
            int $importance = 1,
            array $categories = [],
            ?DateTimeInterface $expiresAt = null,
        ): string {
            return 'mem_123';
        }
    };

    app()->instance(StoreMemoryTool::class, $fakeTool);

    $result = Memory::store('Test content');

    expect($result)->toBe('mem_123');
});
