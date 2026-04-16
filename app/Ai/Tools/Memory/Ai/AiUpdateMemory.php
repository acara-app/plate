<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Ai\Facades\Memory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiUpdateMemory implements Tool
{
    public function name(): string
    {
        return 'update_memory';
    }

    public function description(): string
    {
        return 'Update an existing memory. Use to correct mistakes, refine content, or pin a memory as a Core Truth (set is_pinned=true) when you learn it is permanent.';
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        try {
            $content = $data['content'] ?? null;
            $importance = $data['importance'] ?? null;
            $isPinned = $data['is_pinned'] ?? null;

            $ok = Memory::update(
                (string) ($data['memory_id'] ?? ''),
                is_string($content) ? $content : null,
                null,
                is_numeric($importance) ? (int) $importance : null,
                is_bool($isPinned) ? $isPinned : null,
            );

            return (string) json_encode(['success' => $ok]);
        } catch (MemoryNotFoundException|MemoryStorageException $e) {
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
                ->description('ULID of the memory to update.'),
            'content' => $schema->string()->required()->nullable()
                ->description('New content (null to leave unchanged). Re-embedding happens automatically if changed.'),
            'importance' => $schema->integer()->required()->nullable()
                ->description('New importance 1-10.'),
            'is_pinned' => $schema->boolean()->required()->nullable()
                ->description('Promote to Core Truth (true) or demote (false). Pinned memories are immune to decay and consolidation.'),
        ];
    }
}
