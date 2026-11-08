<?php

declare(strict_types=1);

use App\Console\Commands\AggregateDailyMealEstimatesCommand;
use App\Console\Commands\AggregateHealthDailyCommand;
use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Console\Commands\PurgeDeletedUserDataCommand;
use App\Console\Commands\PurgeExpiredConversationsCommand;
use App\Models\PhotoAnalysisRequest;
use App\Models\SnapPhotoRequest;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

Schedule::command('model:prune', ['--model' => [PhotoAnalysisRequest::class]])->hourly()->withoutOverlapping();

Schedule::command(PurgeExpiredConversationsCommand::class)->hourly()->withoutOverlapping();

Schedule::command(ProcessGlucoseNotificationsCommand::class)->dailyAt('08:00');

Schedule::command(PurgeDeletedUserDataCommand::class)->daily();

Schedule::command(AggregateHealthDailyCommand::class)->dailyAt('02:00');

Schedule::command(AggregateDailyMealEstimatesCommand::class)->dailyAt('02:30')->withoutOverlapping();

Schedule::call(function (): void {
    SnapPhotoRequest::query()->whereNotNull('result')->where('created_at', '<', now('UTC')->subDay())->update(['result' => null]);
})->name('snap:prune-results')->hourly()->withoutOverlapping();
