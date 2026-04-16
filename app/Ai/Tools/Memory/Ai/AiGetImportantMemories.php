<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Facades\Memory;
use App\Data\Memory\MemoryData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiGetImportantMemories implements Tool
{
    public function name(): string
    {
        return 'get_important_memories';
    }

    public function description(): string
    {
        return "Fetch the user's most important memories (by importance score). Use when you need broad context about what matters to the user, not for a specific topic.";
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        $categories = $data['categories'] ?? [];
        $results = Memory::getImportant(
            (int) ($data['threshold'] ?? 8),
            (int) ($data['limit'] ?? 10),
            is_array($categories) ? array_values(array_filter($categories, is_string(...))) : [],
            (bool) ($data['include_archived'] ?? false),
        );

        return (string) json_encode([
            'success' => true,
            'memories' => array_map(
                static fn (MemoryData $m): array => [
                    'id' => $m->id,
                    'content' => $m->content,
                    'importance' => $m->importance,
                    'categories' => $m->categories,
                ],
                $results,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threshold' => $schema->integer()->required()->nullable()
                ->description('Minimum importance 1-10 (default 8).'),
            'limit' => $schema->integer()->required()->nullable()
                ->description('Max memories to return (default 10).'),
            'categories' => $schema->array()
                ->items($schema->string())
                ->description('Optional filter by category labels.'),
            'include_archived' => $schema->boolean()->required()->nullable()
                ->description('Include archived memories.'),
        ];
    }
}
