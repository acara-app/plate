<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use App\Notifications\GlucoseReportNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Process glucose readings and send notifications to users with concerning patterns')]
#[Signature('glucose:process-notifications')]
final class ProcessGlucoseNotificationsCommand extends Command
{
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
