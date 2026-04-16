<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Agents\Memory\MemoryReflectorAgent;
use App\Contracts\Ai\Memory\ReflectOnMemoriesTool;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final readonly class ReflectOnMemories implements ReflectOnMemoriesTool
{
    public function __construct(private MemoryReflectorAgent $agent) {}

    /**
     * @param  array<string>  $categories
     * @return array<int, string>
     */
    public function execute(
        int $lookbackWindow = 50,
        ?string $context = null,
        array $categories = [],
    ): array {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()
            ->where('is_archived', false)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if ($categories !== []) {
            $query->where(function (Builder $q) use ($categories): void {
                foreach ($categories as $category) {
                    $q->orWhereJsonContains('categories', $category);
                }
            });
        }

        $memories = $query->latest()
            ->limit($lookbackWindow)
            ->get();

        if ($memories->isEmpty()) {
            return [];
        }

        $formatted = $memories
            ->reverse()
            ->map(static fn (Memory $memory): string => sprintf(
                '- [importance=%d, categories=%s] %s',
                $memory->importance,
                implode(',', $memory->categories ?? []),
                $memory->content,
            ))
            ->implode("\n");

        $prompt = "Synthesize insights from these recent memories:\n\n".$formatted;

        if ($context !== null && $context !== '') {
            $prompt .= "\n\nFocus on: ".$context;
        }

        return $this->agent->reflect($prompt);
    }
}
