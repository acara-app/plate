<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\SummarizesConversation;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Utilities\ConfigHelper;
use App\Utilities\JsonCleaner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class SummarizeConversationAction
{
    public function __construct(private SummarizesConversation $agent) {}

    public function handle(Conversation $conversation): ?ConversationSummary
    {
        try {
            if (! $this->shouldSummarize($conversation)) {
                return null;
            }

            $messages = $this->getMessagesToSummarize($conversation);

            if ($messages->isEmpty()) {
                return null; // @codeCoverageIgnore
            }

            $previousSummary = ConversationSummary::getLatestForConversation($conversation->id);
            $conversationText = $this->formatMessages($messages);
            $rawResponse = $this->agent->summarize($conversationText, $previousSummary);
            $cleanedJson = JsonCleaner::extractAndValidateJson($rawResponse);

            /** @var array{summary: string, topics: array<string>, key_facts: array<string>, unresolved_threads: array<string>, resolved_threads: array<string>} $summaryData */
            $summaryData = json_decode($cleanedJson, true, 512, JSON_THROW_ON_ERROR);

            return $this->createSummary($conversation, $messages, $summaryData, $previousSummary);
        } catch (Throwable $throwable) {
            Log::error('Conversation summarization failed', [
                'conversation_id' => $conversation->id,
                'error' => $throwable->getMessage(),
                'trace' => Str::limit($throwable->getTraceAsString(), 1000),
            ]);

            return null;
        }
    }

    public function shouldSummarize(Conversation $conversation): bool
    {
        $totalCount = History::query()->where('conversation_id', $conversation->id)->count();
        $buffer = ConfigHelper::int('altani.summarization.buffer', 25);

        if ($totalCount <= $buffer) {
            return false;
        }

        $bufferedIds = History::query()->where('conversation_id', $conversation->id)->latest()
            ->take($buffer)
            ->pluck('id');

        $unsummarizedOld = History::query()->where('conversation_id', $conversation->id)
            ->whereNull('summary_id')
            ->whereNotIn('id', $bufferedIds)
            ->count();

        return $unsummarizedOld >= ConfigHelper::int('altani.summarization.threshold', 20);
    }

    /**
     * @return Collection<int, History>
     */
    private function getMessagesToSummarize(Conversation $conversation): Collection
    {
        $buffer = ConfigHelper::int('altani.summarization.buffer', 25);

        $bufferedIds = History::query()->where('conversation_id', $conversation->id)->latest()
            ->take($buffer)
            ->pluck('id');

        return History::query()->where('conversation_id', $conversation->id)
            ->whereNull('summary_id')
            ->whereNotIn('id', $bufferedIds)->oldest()
            ->get();
    }

    /**
     * @param  Collection<int, History>  $messages
     */
    private function formatMessages(Collection $messages): string
    {
        return $messages
            ->map(fn (History $m): string => sprintf('[%s] %s: %s', $m->created_at->format('M j, g:ia'), $m->role->value, $m->content))
            ->join("\n\n");
    }

    /**
     * @param  Collection<int, History>  $messages
     * @param  array<string, mixed>  $summaryData
     */
    private function createSummary(
        Conversation $conversation,
        Collection $messages,
        array $summaryData,
        ?ConversationSummary $previousSummary,
    ): ConversationSummary {
        return DB::transaction(function () use ($conversation, $messages, $summaryData, $previousSummary): ConversationSummary {
            $firstMessage = $messages->first();
            $lastMessage = $messages->last();
            assert($firstMessage !== null);
            assert($lastMessage !== null);

            $summary = ConversationSummary::query()->create([
                'conversation_id' => $conversation->id,
                'sequence_number' => ConversationSummary::getNextSequenceNumber($conversation->id),
                'previous_summary_id' => $previousSummary?->id,
                'summary' => $summaryData['summary'],
                'topics' => $summaryData['topics'] ?? [],
                'key_facts' => $summaryData['key_facts'] ?? [],
                'unresolved_threads' => $summaryData['unresolved_threads'] ?? [],
                'resolved_threads' => $summaryData['resolved_threads'] ?? [],
                'start_message_id' => $firstMessage->id,
                'end_message_id' => $lastMessage->id,
                'message_count' => $messages->count(),
            ]);

            History::query()->whereIn('id', $messages->pluck('id'))
                ->update(['summary_id' => $summary->id]);

            return $summary;
        });
    }
}
