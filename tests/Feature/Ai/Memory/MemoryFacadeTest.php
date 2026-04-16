<?php

declare(strict_types=1);

use App\Ai\Facades\Memory;
use App\Contracts\Ai\Memory\StoreMemoryTool;
use App\Data\Memory\MemoryStatsData;

it('proxies static calls to the bound contract', function (): void {
    $stub = new class implements StoreMemoryTool
    {
        public ?array $lastArgs = null;

        public function execute(
            string $content,
            array $metadata = [],
            ?array $vector = null,
            int $importance = 1,
            array $categories = [],
            ?DateTimeInterface $expiresAt = null,
            ?string $memoryType = null,
        ): string {
            $this->lastArgs = ['content' => $content, 'metadata' => $metadata, 'vector' => $vector, 'importance' => $importance, 'categories' => $categories, 'memoryType' => $memoryType];

            return 'stub-id';
        }
    };

    app()->instance(StoreMemoryTool::class, $stub);

    $id = Memory::store('hello', ['topic' => 't'], null, 5, ['a']);

    expect($id)->toBe('stub-id')
        ->and($stub->lastArgs['content'])->toBe('hello')
        ->and($stub->lastArgs['importance'])->toBe(5)
        ->and($stub->lastArgs['categories'])->toBe(['a']);
});

it('throws for unknown methods', function (): void {
    Memory::bogus();
})->throws(BadMethodCallException::class);

it('returns getStats through the facade', function (): void {
    /** @var MemoryStatsData $stats */
    $stats = Memory::getStats();

    expect($stats)->toBeInstanceOf(MemoryStatsData::class)
        ->and($stats->totalMemories)->toBe(0);
});
