<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use App\Notifications\GlucoseReportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessGlucoseNotificationsCommand extends Command
{
    protected $signature = 'glucose:process-notifications';

    protected $description = 'Process glucose readings and send notifications to users with concerning patterns';

    public function handle(AnalyzeGlucoseForNotificationAction $action): int
    {
        $this->info('Processing glucose notifications...');

        $processedCount = 0;
        $notifiedCount = 0;

        User::query()
            ->whereNotNull('settings')
            ->whereJsonContains('settings->glucose_notifications_enabled', true)
            ->whereNotNull('email_verified_at')
            ->cursor()
            ->each(function (User $user) use ($action, &$processedCount, &$notifiedCount): void {
                $processedCount++;
                if ($this->processUserNotification($user, $action)) {
                    $notifiedCount++;
                }
            });

        $this->info("Processed {$processedCount} users, sent {$notifiedCount} notifications.");

        return self::SUCCESS;
    }

    private function processUserNotification(User $user, AnalyzeGlucoseForNotificationAction $action): bool
    {
        try {
            $result = $action->handle($user);

            if ($result->shouldNotify) {
                $user->notify(new GlucoseReportNotification($result));

                return true;
            }

            return false;
        } catch (Throwable $e) {
            Log::error('Failed to process glucose notification for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
