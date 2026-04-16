<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Contracts\Ai\Memory\StoreMemoryTool;
use App\Enums\MemoryType;
use App\Models\Memory;
use App\Services\Memory\EmbeddingService;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class StoreMemory implements StoreMemoryTool
{
    public function __construct(private EmbeddingService $embedder) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<float>|null  $vector
     * @param  array<string>  $categories
     */
    public function execute(
        string $content,
        array $metadata = [],
        ?array $vector = null,
        int $importance = 1,
        array $categories = [],
        ?DateTimeInterface $expiresAt = null,
        ?string $memoryType = null,
    ): string {
        $userId = $this->resolveUserId($metadata);

        if ($userId === null) {
            throw MemoryStorageException::storeFailed(
                'Unable to resolve user_id: no authenticated user and no metadata[user_id] supplied.',
                ['metadata' => $metadata],
            );
        }

        $metadata['user_id'] = $userId;

        $source = is_string($metadata['source'] ?? null) ? $metadata['source'] : null;
        /** @phpstan-ignore cast.int */
        $minImportance = (int) config('memory.importance.min', 1);
        /** @phpstan-ignore cast.int */
        $maxImportance = (int) config('memory.importance.max', 10);
        $clampedImportance = max($minImportance, min($maxImportance, $importance));
        $resolvedType = $this->resolveMemoryType($memoryType);

        try {
            return DB::transaction(function () use ($content, $metadata, $vector, $clampedImportance, $categories, $expiresAt, $userId, $source, $resolvedType): string {
                /** @var array<int, float> $embedding */
                $embedding = $vector ?? $this->embedder->generate($content);

                $memory = new Memory;
                $memory->forceFill([
                    'user_id' => $userId,
                    'content' => $content,
                    'metadata' => $metadata,
                    'categories' => $categories,
                    'importance' => $clampedImportance,
                    'source' => $source,
                    'expires_at' => $expiresAt,
                    'memory_type' => $resolvedType,
                ]);
                $memory->setEmbedding($embedding);
                $memory->save();

                return $memory->id;
            });
        } catch (Throwable $throwable) {
            throw MemoryStorageException::storeFailed($throwable->getMessage(), ['content' => $content]);
        }
    }

    private function resolveMemoryType(?string $memoryType): ?MemoryType
    {
        if ($memoryType === null || $memoryType === '') {
            return null;
        }

        return MemoryType::tryFrom($memoryType);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function resolveUserId(array $metadata): ?int
    {
        if (isset($metadata['user_id']) && is_numeric($metadata['user_id'])) {
            return (int) $metadata['user_id'];
        }

        $authId = Auth::id();

        return is_numeric($authId) ? (int) $authId : null;
    }
}
