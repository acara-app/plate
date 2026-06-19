<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DeleteConversationHistory;
use App\Models\Conversation;
use App\Services\StreamEventStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Messages\MessageRole;

#[Description('Permanently delete temporary (unpinned) conversations whose last activity is older than the retention window')]
#[Signature('conversations:purge-expired {--limit=1000 : Maximum conversations to delete in this run}')]
final class PurgeExpiredConversationsCommand extends Command
{
    public function __construct(private readonly DeleteConversationHistory $deleteConversationHistory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $count = 0;
        $reachedLimit = false;

        Conversation::query()
            ->expired()
            ->with(['messages' => fn ($query) => $query
                ->where('role', MessageRole::Assistant->value)
                ->where('created_at', '>', now()->subSeconds(StreamEventStore::TTL_SECONDS))])
            ->chunkById(100, function (Collection $conversations) use (&$count, &$reachedLimit, $limit): bool {
                foreach ($conversations as $conversation) {
                    if ($count >= $limit) {
                        $reachedLimit = true;

                        return false;
                    }

                    if ($conversation->hasPendingChatStream()) {
                        continue;
                    }

                    if ($this->purge($conversation)) {
                        $count++;
                    }
                }

                return true;
            });

        $this->info($count === 0
            ? 'No expired conversations to purge.'
            : sprintf('Purged %d expired conversation(s).', $count));

        if ($reachedLimit) {
            $this->warn(sprintf('Reached the per-run limit of %d. Re-run to purge the remainder.', $limit));
        }

        return self::SUCCESS;
    }

    private function purge(Conversation $conversation): bool
    {
        return DB::transaction(function () use ($conversation): bool {
            $locked = Conversation::query()
                ->whereKey($conversation->getKey())
                ->whereNull('pinned_at')
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof Conversation) {
                return false;
            }

            $this->deleteConversationHistory->handle($locked);

            return true;
        });
    }
}
