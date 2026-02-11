<?php

declare(strict_types=1);

use App\Console\Commands\RegisterTelegramCommands;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Telegraph as TelegraphClient;

beforeEach(function (): void {
    Telegraph::fake();
});

it('registers telegram commands successfully when bot exists', function (): void {
    $bot = TelegraphBot::factory()->create([
        'name' => 'TestBot',
    ]);

    Telegraph::fake([
        TelegraphClient::ENDPOINT_REGISTER_BOT_COMMANDS => [
            'ok' => true,
            'result' => true,
        ],
    ]);

    $this->artisan(RegisterTelegramCommands::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Registering commands for bot: TestBot')
        ->expectsOutputToContain('Commands registered successfully!');
});

it('fails when no telegraph bot exists', function (): void {
    $this->artisan(RegisterTelegramCommands::class)
        ->assertFailed()
        ->expectsOutputToContain('No Telegraph bot found in the database.');
});
