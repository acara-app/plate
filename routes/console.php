<?php

declare(strict_types=1);

use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

Schedule::command(ProcessGlucoseNotificationsCommand::class)->dailyAt('08:00');
