<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory\Ai;

use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Ai\Facades\Memory;
use App\Enums\MemoryType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class AiStoreMemory implements Tool
{
    public function name(): string
    {
        return 'store_memory';
    }

    public function description(): string
    {
        return 'Save a long-term memory about the user — preferences, goals, important facts, recurring constraints. Use sparingly for genuinely stable personal facts, not for one-off details. Do not duplicate anything already in the user profile.';
    }

    public function handle(Request $request): string
    {
        $data = $request->toArray();

        try {
            $categories = $data['categories'] ?? [];
            $memoryType = $data['memory_type'] ?? null;
            $id = Memory::store(
                (string) ($data['content'] ?? ''),
                [],
                null,
                (int) ($data['importance'] ?? 5),
                is_array($categories) ? array_values(array_filter($categories, is_string(...))) : [],
                null,
                is_string($memoryType) && $memoryType !== '' ? $memoryType : null,
            );

            return (string) json_encode(['success' => true, 'memory_id' => $id]);
        } catch (MemoryStorageException $memoryStorageException) {
            return (string) json_encode(['success' => false, 'error' => $memoryStorageException->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->required()
                ->description('Full, self-contained statement of the memory (e.g. "User is vegetarian and avoids eggs").'),
            'importance' => $schema->integer()->required()->nullable()
                ->description('Priority score 1-10. Use 8-10 only for critical, always-applicable facts like allergies.'),
            'categories' => $schema->array()
                ->items($schema->string())
                ->description('Optional category labels (e.g. ["health", "preference"]).'),
            'memory_type' => $schema->string()->required()->nullable()
                ->enum(MemoryType::class)
                ->description('Semantic type of the memory.'),
        ];
    }
}
