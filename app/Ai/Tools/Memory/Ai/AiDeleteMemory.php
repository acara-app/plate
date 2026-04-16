<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Exceptions\Memory\InvalidMemoryFilterException;
use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Facades\Memory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiDeleteMemory implements Tool
{
    public function name(): string
    {
        return 'delete_memory';
    }

    public function description(): string
    {
        return 'Delete a memory by ID. Use only when the user explicitly asks to forget a fact you previously stored.';
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        try {
            $deleted = Memory::delete(
                (string) ($data['memory_id'] ?? ''),
                [],
            );

            return (string) json_encode(['success' => true, 'deleted' => $deleted]);
        } catch (InvalidMemoryFilterException|MemoryNotFoundException $e) {
            return (string) json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'memory_id' => $schema->string()->required()
                ->description('ULID of the memory to delete.'),
        ];
    }
}
