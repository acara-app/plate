<?php

declare(strict_types=1);

use App\Console\Commands\AggregateHealthDailyCommand;
use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Console\Commands\PurgeDeletedUserDataCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

Schedule::command(ProcessGlucoseNotificationsCommand::class)->dailyAt('08:00');

Schedule::command(PurgeDeletedUserDataCommand::class)->daily();

Schedule::command(AggregateHealthDailyCommand::class)->everyFifteenMinutes();
