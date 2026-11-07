<?php

declare(strict_types=1);

use App\Console\Commands\AggregateHealthDailyCommand;
use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Console\Commands\PurgeDeletedUserDataCommand;
use App\Console\Commands\PurgeExpiredConversationsCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

Schedule::command(PurgeExpiredConversationsCommand::class)->hourly()->withoutOverlapping();

Schedule::command(ProcessGlucoseNotificationsCommand::class)->dailyAt('08:00');

Schedule::command(PurgeDeletedUserDataCommand::class)->daily();

Schedule::command(AggregateHealthDailyCommand::class)->dailyAt('02:00');

Schedule::call(function (): void {
    DB::table('snap_photo_requests')->whereNotNull('result')->where('created_at', '<', now('UTC')->subDay())->update(['result' => null]);
})->name('snap:prune-results')->hourly()->withoutOverlapping();
