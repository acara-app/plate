<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeletedUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PurgeDeletedUserDataCommand extends Command
{
    protected $signature = 'users:purge-deleted-data';

    protected $description = 'Purge orphaned data for users deleted more than 30 days ago';

    public function handle(): int
    {
        $count = 0;

        DeletedUser::query()
            ->where('deleted_at', '<=', now()->subDays(30))
            ->chunkById(100, function ($deletedUsers) use (&$count): void {
                $deletedUsers->each(function (DeletedUser $deletedUser) use (&$count): void {
                    $this->purgeUserData($deletedUser);
                    $count++;
                });
            });

        $this->info($count === 0
            ? 'No user data to purge.'
            : "Purged data for {$count} deleted user(s).");

        return self::SUCCESS;
    }

    private function purgeUserData(DeletedUser $deletedUser): void
    {
        DB::transaction(function () use ($deletedUser): void {
            $conversationIds = DB::table('agent_conversations')
                ->where('user_id', $deletedUser->user_id)
                ->pluck('id');

            if ($conversationIds->isNotEmpty()) {
                DB::table('conversation_summaries')
                    ->whereIn('conversation_id', $conversationIds)
                    ->delete();
            }

            DB::table('agent_conversation_messages')
                ->where('user_id', $deletedUser->user_id)
                ->delete();

            DB::table('agent_conversations')
                ->where('user_id', $deletedUser->user_id)
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
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $deletedUser->user_id)
                ->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $deletedUser->user_id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $deletedUser->email)
                ->delete();

            $deletedUser->delete();
        });
    }
}
