<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Ai\Facades\Memory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiLinkMemories implements Tool
{
    public function name(): string
    {
        return 'link_memories';
    }

    public function description(): string
    {
        return 'Link two or more related memories so future retrieval can walk between them. Use for obvious relationships (cause→effect, parent topic→sub-fact, same goal).';
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        try {
            $memoryIds = $data['memory_ids'] ?? [];
            $linked = Memory::link(
                is_array($memoryIds) ? array_values(array_filter($memoryIds, is_string(...))) : [],
                (string) ($data['relationship'] ?? 'related'),
                (bool) ($data['bidirectional'] ?? true),
            );

            return (string) json_encode(['success' => $linked]);
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
            'memory_ids' => $schema->array()
                ->items($schema->string())
                ->min(2)
                ->description('At least two memory ULIDs to link.'),
            'relationship' => $schema->string()->required()->nullable()
                ->description('Relationship label (e.g. "related", "causes", "part_of"). Default: "related".'),
            'bidirectional' => $schema->boolean()->required()->nullable()
                ->description('Create the reverse link too (default true).'),
        ];
    }
}
