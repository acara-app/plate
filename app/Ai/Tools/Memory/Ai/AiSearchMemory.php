<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Facades\Memory;
use App\Data\Memory\MemorySearchResultData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiSearchMemory implements Tool
{
    public function name(): string
    {
        return 'search_memory';
    }

    public function description(): string
    {
        return "Search the user's long-term memories by semantic similarity. Use when you suspect a relevant fact exists but was not surfaced in the CORE TRUTHS or RECALLED MEMORIES blocks.";
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        $results = Memory::search(
            (string) ($data['query'] ?? ''),
            (int) ($data['limit'] ?? 5),
            (float) ($data['min_relevance'] ?? 0.7),
            [],
            (bool) ($data['include_archived'] ?? false),
        );

        return (string) json_encode([
            'success' => true,
            'results' => array_map(
                static fn (MemorySearchResultData $r): array => [
                    'id' => $r->id,
                    'content' => $r->content,
                    'score' => $r->score,
                    'categories' => $r->categories,
                    'importance' => $r->importance,
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
            'query' => $schema->string()->required()
                ->description('Natural-language query for semantic search.'),
            'limit' => $schema->integer()->required()->nullable()
                ->description('Maximum results to return (default 5).'),
            'min_relevance' => $schema->number()->required()->nullable()
                ->description('Minimum cosine similarity, 0.0 to 1.0 (default 0.7).'),
            'include_archived' => $schema->boolean()->required()->nullable()
                ->description('Include archived memories in the search.'),
        ];
    }
}
