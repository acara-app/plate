<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeletedUser;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Description('Purge orphaned data for users deleted more than 30 days ago')]
#[Signature('users:purge-deleted-data')]
final class PurgeDeletedUserDataCommand extends Command
{
    public function handle(): int
    {
        $count = 0;

        DeletedUser::query()
            ->where('deleted_at', '<=', now()->subDays(30))
            ->chunkById(100, function (Collection $deletedUsers) use (&$count): void {
                $deletedUsers->each(function (DeletedUser $deletedUser) use (&$count): void {
                    $this->purgeUserData($deletedUser);
                    $count++;
                });
            });

        $leftovers = $this->purgeLeftovers();

        $this->info($count === 0
            ? 'No user data to purge.'
            : sprintf('Purged data for %d deleted user(s).', $count));

        if ($leftovers > 0) {
            $this->info(sprintf('Removed %d token(s) and notification(s) left by deleted accounts.', $leftovers));
        }

        return self::SUCCESS;
    }

    private function purgeUserData(DeletedUser $deletedUser): void
    {
        DB::transaction(function () use ($deletedUser): void {
            $conversationIds = DB::table('agent_conversations')
                ->where('participant_type', 'user')
                ->where('participant_id', $deletedUser->user_id)
                ->pluck('id');

            if ($conversationIds->isNotEmpty()) {
                DB::table('conversation_summaries')
                    ->whereIn('conversation_id', $conversationIds)
                    ->delete();
            }

            DB::table('agent_conversation_messages')
                ->where('participant_type', 'user')
                ->where('participant_id', $deletedUser->user_id)
                ->delete();

            DB::table('agent_conversations')
                ->where('participant_type', 'user')
                ->where('participant_id', $deletedUser->user_id)
                ->delete();

            $subscriptionIds = DB::table('subscriptions')
                ->where('user_id', $deletedUser->user_id)
                ->pluck('id');

            if ($subscriptionIds->isNotEmpty()) {
                DB::table('subscription_items')
                    ->whereIn('subscription_id', $subscriptionIds)
                    ->delete();
            }

            DB::table('subscriptions')
                ->where('user_id', $deletedUser->user_id)
                ->delete();

            DB::table('sessions')
                ->where('user_id', $deletedUser->user_id)
                ->delete();

            DB::table('notifications')
                ->whereIn('notifiable_type', $this->userMorphTypes())
                ->where('notifiable_id', $deletedUser->user_id)
                ->delete();

            DB::table('personal_access_tokens')
                ->whereIn('tokenable_type', $this->userMorphTypes())
                ->where('tokenable_id', $deletedUser->user_id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $deletedUser->email)
                ->delete();

            $deletedUser->delete();
        });
    }

    private function purgeLeftovers(): int
    {
        $existingUsers = DB::table('users')->select('id');
        $pendingDeletions = DB::table('deleted_users')->select('user_id');

        return DB::table('personal_access_tokens')
            ->whereIn('tokenable_type', $this->userMorphTypes())
            ->whereNotIn('tokenable_id', $existingUsers)
            ->whereNotIn('tokenable_id', $pendingDeletions)
            ->delete()
            + DB::table('notifications')
                ->whereIn('notifiable_type', $this->userMorphTypes())
                ->whereNotIn('notifiable_id', $existingUsers)
                ->whereNotIn('notifiable_id', $pendingDeletions)
                ->delete();
    }

    /**
     * @return list<string>
     */
    private function userMorphTypes(): array
    {
        return array_values(array_unique([User::class, new User()->getMorphClass()]));
    }
}
