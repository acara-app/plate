<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IntegrationsController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $telegramChat = $user->telegramChat;

        return Inertia::render('integrations/edit', [
            'telegram' => [
                'is_connected' => $telegramChat?->linked_at !== null,
                'linking_token' => $telegramChat?->linking_token,
                'token_expires_at' => $telegramChat?->token_expires_at?->toIso8601String(),
                'connected_at' => $telegramChat?->linked_at?->toIso8601String(),
                'bot_username' => config('plate.telegram_bot_username'),
            ],
        ]);
    }

    public function generateTelegramToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $user->telegramChat?->update(['is_active' => false]);

        $newLink = $user->chatPlatformLinks()->create([
            'platform' => 'telegram',
            'is_active' => true,
        ]);

        $token = $newLink->generateToken(expiresInHours: 24);

        return to_route('integrations.edit')->with([
            'telegram_token' => $token,
            'token_expires_at' => $newLink->token_expires_at?->toIso8601String(),
        ]);
    }

    public function disconnectTelegram(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $user->chatPlatformLinks()
            ->where('platform', 'telegram')
            ->update(['is_active' => false]);

        return to_route('integrations.edit')->with('status', 'telegram-disconnected');
    }
}
