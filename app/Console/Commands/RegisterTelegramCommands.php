<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;

final class RegisterTelegramCommands extends Command
{
    protected $signature = 'telegram:register-commands';

    protected $description = 'Register Telegram bot menu commands';

    public function handle(): int
    {
        $bot = TelegraphBot::query()->first();

        if (! $bot instanceof TelegraphBot) {
            $this->error('No Telegraph bot found in the database.');

            return self::FAILURE;
        }

        $this->info("Registering commands for bot: {$bot->name}");

        $response = $bot->registerCommands([
            'start' => 'Welcome message & getting started',
            'new' => 'Start a new conversation',
            'reset' => 'Clear conversation history',
            'me' => 'Show your profile',
            'help' => 'Show all commands',
        ])->send();

        if ($response->ok()) {
            $this->info('✅ Commands registered successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to register commands: '.$response->body());

        return self::FAILURE;
    }
}
