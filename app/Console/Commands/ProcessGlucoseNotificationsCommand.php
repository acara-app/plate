<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use App\Notifications\GlucoseReportNotification;
use Illuminate\Console\Command;

final class ProcessGlucoseNotificationsCommand extends Command
{
    protected $signature = 'glucose:process-notifications';

    protected $description = 'Process glucose readings and send notifications to users with concerning patterns';

    public function handle(AnalyzeGlucoseForNotificationAction $action): int
    {

        User::query()
            ->whereNotNull('settings')
            ->whereJsonContains('settings->glucose_notifications_enabled', true)
            ->whereNotNull('email_verified_at')
            ->cursor()
            ->each(function (User $user) use ($action): void {
                $result = $action->handle($user);

                if ($result->shouldNotify) {
                    $user->notify(new GlucoseReportNotification($result));
                }

            });

        return self::SUCCESS;
    }
}
