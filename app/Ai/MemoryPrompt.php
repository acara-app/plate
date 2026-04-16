<?php

declare(strict_types=1);

namespace App\Ai;

use App\Data\Memory\MemorySearchResultData;
use App\Models\Memory;
use App\Services\Memory\ContextRetriever;
use Illuminate\Support\Collection;
use Stringable;

final class MemoryPrompt implements Stringable
{
    private ?int $userId = null;

    private string $userMessage = '';

    /**
     * @var array<int, array{role: string, content: string}>
     */
    private array $conversationTail = [];

    public function __construct(
        private readonly ContextRetriever $contextRetriever,
    ) {}

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $conversationTail
     */
    public function for(int $userId, string $userMessage, array $conversationTail = []): self
    {
        $this->userId = $userId;
        $this->userMessage = $userMessage;
        $this->conversationTail = $conversationTail;

        return $this;
    }

    public function render(): string
    {
        if ($this->userId === null || $this->userId <= 0) {
            return '';
        }

        $truths = $this->fetchTruths($this->userId);
        $truthIds = $truths->pluck('id')->all();

        $recalled = $this->contextRetriever
            ->recall($this->userId, $this->userMessage, $this->conversationTail)
            ->reject(static fn (MemorySearchResultData $memory): bool => in_array($memory->id, $truthIds, true))
            ->values();

        return $this->format($truths, $recalled);
    }

    /**
     * @return Collection<int, Memory>
     */
    private function fetchTruths(int $userId): Collection
    {
        /** @phpstan-ignore cast.int */
        $limit = (int) config('memory.truths.max_results', 5);

        /** @var Collection<int, Memory> $rows */
        $rows = Memory::query()
            ->forUser($userId)
            ->active()
            ->pinned()
            ->orderByDesc('importance')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return $rows;
    }

    /**
     * @param  Collection<int, Memory>  $truths
     * @param  Collection<int, MemorySearchResultData>  $recalled
     */
    private function format(Collection $truths, Collection $recalled): string
    {
        $sections = [];

        if ($truths->isNotEmpty()) {
            $lines = $truths
                ->map(static function (Memory $memory): string {
                    $categories = ($memory->categories ?? []) === [] ? '' : ' ['.implode(', ', $memory->categories ?? []).']';

                    return sprintf(' - %s%s', $memory->content, $categories);
                })
                ->implode("\n");

            $sections[] = "# CORE TRUTHS\n".$lines;
        }

        if ($recalled->isNotEmpty()) {
            $lines = $recalled
                ->map(static function (MemorySearchResultData $memory): string {
                    $categories = $memory->categories === [] ? '' : ' ['.implode(', ', $memory->categories).']';

                    return sprintf(' - (%s) %s%s', number_format($memory->score, 2), $memory->content, $categories);
                })
                ->implode("\n");

            $sections[] = "# RECALLED MEMORIES\n".$lines;
        }

        return implode("\n\n", $sections);
    }
}
