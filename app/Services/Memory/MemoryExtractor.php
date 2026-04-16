<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Ai\Facades\Memory;
use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Data\Memory\ExtractedMemoryData;
use App\Enums\MemoryType;
use App\Models\History;
use App\Models\MemoryExtractionCheckpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Messages\MessageRole;
use Throwable;

final readonly class MemoryExtractor
{
    public function __construct(private ExtractsMemoriesFromConversation $agent) {}

    public function extractForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $pending = $this->pendingHistoryFor($userId);

        if ($pending->isEmpty()) {
            return 0;
        }

        try {
            $formatted = $this->formatConversation($pending);
            $result = $this->agent->extractFromConversation($formatted);

            if (! $result['should_extract']) {
                $this->advanceCheckpoint($userId, $pending, 0);

                return 0;
            }

            /** @phpstan-ignore cast.int */
            $maxMemories = (int) config('memory.extraction.max_memories', 6);
            $memories = array_slice($result['memories'], 0, $maxMemories);

            $count = $this->persistMemories($userId, $memories);
            $this->advanceCheckpoint($userId, $pending, $count);

            return $count;
        } catch (Throwable $throwable) {
            Log::warning('Memory extraction failed', [
                'user_id' => $userId,
                'error' => $throwable->getMessage(),
            ]);

            return 0;
        }
    }

    public function shouldExtract(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        /** @phpstan-ignore cast.int */
        $threshold = (int) config('memory.extraction.threshold', 10);

        return $this->pendingHistoryQuery($userId)->count() >= $threshold;
    }

    /**
     * @return Collection<int, History>
     */
    public function pendingHistoryFor(int $userId, ?int $limit = null): Collection
    {
        /** @phpstan-ignore cast.int */
        $resolvedLimit = $limit ?? (int) config('memory.extraction.max_turns', 40);

        /** @var Collection<int, History> $rows */
        $rows = $this->pendingHistoryQuery($userId)->oldest()
            ->orderBy('id')
            ->limit($resolvedLimit)
            ->get();

        return $rows;
    }

    /**
     * @return Builder<History>
     */
    private function pendingHistoryQuery(int $userId): Builder
    {
        $checkpoint = MemoryExtractionCheckpoint::query()->where('user_id', $userId)->first();
        $cursorAt = $checkpoint?->last_extracted_message_at;
        $cursorId = $checkpoint?->last_extracted_message_id;

        /** @var Builder<History> $query */
        $query = History::query()
            ->where('user_id', $userId)
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value]);

        if ($cursorAt !== null) {
            $query->where(function (Builder $q) use ($cursorAt, $cursorId): void {
                $q->where('created_at', '>', $cursorAt)
                    ->orWhere(function (Builder $q2) use ($cursorAt, $cursorId): void {
                        $q2->where('created_at', '=', $cursorAt)
                            ->when($cursorId !== null, fn (Builder $q3) => $q3->where('id', '>', $cursorId));
                    });
            });
        }

        return $query;
    }

    /**
     * @param  Collection<int, History>  $pending
     */
    private function formatConversation(Collection $pending): string
    {
        $lines = $pending->map(static function (History $message): string {
            $timestamp = '['.$message->created_at->toIso8601String().'] ';
            $role = $message->role->value;

            return $timestamp.$role.': '.$message->content;
        })->all();

        return implode("\n\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $memoriesData
     */
    private function persistMemories(int $userId, array $memoriesData): int
    {
        $count = 0;

        foreach ($memoriesData as $data) {
            try {
                $extracted = new ExtractedMemoryData(
                    content: (string) ($data['content'] ?? ''),
                    memoryType: (string) ($data['memory_type'] ?? MemoryType::Fact->value),
                    categories: $this->normalizeCategories($data['categories'] ?? []),
                    importance: (int) ($data['importance'] ?? 5),
                    context: isset($data['context']) && is_string($data['context']) ? $data['context'] : null,
                );

                if ($extracted->content === '') {
                    continue;
                }

                $metadata = [
                    'user_id' => $userId,
                    'source' => 'extraction',
                    'context' => $extracted->context,
                    'extracted_at' => now()->toIso8601String(),
                ];

                Memory::store(
                    $extracted->content,
                    $metadata,
                    null,
                    $extracted->importance,
                    $extracted->categories,
                    null,
                    $extracted->memoryType,
                );

                $count++;
            } catch (Throwable $e) {
                Log::warning('Failed to persist extracted memory', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'content' => (string) ($data['content'] ?? ''),
                ]);
            }
        }

        return $count;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeCategories(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(
            $raw,
            static fn (mixed $c): bool => is_string($c) && mb_trim($c) !== '',
        ));
    }

    /**
     * @param  Collection<int, History>  $pending
     */
    private function advanceCheckpoint(int $userId, Collection $pending, int $extracted): void
    {
        $last = $pending->last();

        MemoryExtractionCheckpoint::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'last_extracted_at' => now(),
                'last_extracted_message_id' => $last?->id,
                'last_extracted_message_at' => $last?->created_at,
                'extracted_count' => $extracted,
            ],
        );
    }
}
