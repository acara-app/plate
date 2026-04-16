<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Facades\Memory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiGetMemory implements Tool
{
    public function name(): string
    {
        return 'get_memory';
    }

    public function description(): string
    {
        return 'Fetch a specific memory by its ID. Use only when a prior tool returned an ID you need to re-read.';
    }

    public function handle(Request $request): string
    {
        $payload = $request->toArray();

        try {
            $data = Memory::get(
                (string) ($payload['memory_id'] ?? ''),
                (bool) ($payload['include_archived'] ?? false),
            );

            return (string) json_encode(['success' => true, 'memory' => $data->toArray()]);
        } catch (MemoryNotFoundException $memoryNotFoundException) {
            return (string) json_encode(['success' => false, 'error' => $memoryNotFoundException->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'memory_id' => $schema->string()->required()
                ->description('ULID of the memory to fetch.'),
            'include_archived' => $schema->boolean()->required()->nullable()
                ->description('Include archived memories.'),
        ];
    }
}
