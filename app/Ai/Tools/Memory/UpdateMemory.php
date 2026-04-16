<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Contracts\Ai\Memory\UpdateMemoryTool;
use App\Models\Memory;
use App\Services\Memory\EmbeddingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateMemory implements UpdateMemoryTool
{
    public function __construct(private EmbeddingService $embedder) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(
        string $memoryId,
        ?string $content = null,
        ?array $metadata = null,
        ?int $importance = null,
        ?bool $isPinned = null,
    ): bool {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->where('id', $memoryId);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $memory = $query->first();

        throw_unless($memory instanceof Memory, MemoryNotFoundException::class, $memoryId);

        try {
            return DB::transaction(function () use ($memory, $content, $metadata, $importance, $isPinned): bool {
                $contentChanged = $content !== null && $content !== $memory->content;

                if ($contentChanged) {
                    $memory->content = $content;
                    $memory->setEmbedding($this->embedder->generate($content));
                }

                if ($metadata !== null) {
                    $memory->metadata = array_merge($memory->metadata ?? [], $metadata);
                }

                if ($importance !== null) {
                    /** @phpstan-ignore cast.int */
                    $min = (int) config('memory.importance.min', 1);
                    /** @phpstan-ignore cast.int */
                    $max = (int) config('memory.importance.max', 10);
                    $memory->importance = max($min, min($max, $importance));
                }

                if ($isPinned !== null) {
                    $memory->is_pinned = $isPinned;
                }

                return $memory->save();
            });
        } catch (Throwable $throwable) {
            throw MemoryStorageException::updateFailed($memoryId, $throwable->getMessage());
        }
    }
}
