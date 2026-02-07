<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserTelegramChat;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class IntegrationsController
{

    public function edit(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $telegramChat = $user->telegramChat;

        return Inertia::render('integrations/edit', [
            'telegram' => [
                'is_connected' => $telegramChat instanceof UserTelegramChat && $telegramChat->is_active && $telegramChat->linked_at !== null,
                'linking_token' => $telegramChat?->linking_token,
                'token_expires_at' => $telegramChat?->token_expires_at?->toIso8601String(),
                'connected_at' => $telegramChat?->linked_at?->toIso8601String(),
            ],
        ]);
    }


    public function generateTelegramToken(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        $user->telegramChat()->active()->update(['is_active' => false]);

        $userTelegramChat = $user->telegramChat()->create([
            'is_active' => true,
        ]);

        $token = $userTelegramChat->generateToken();

        return to_route('integrations.edit')->with([
            'telegram_token' => $token,
            'token_expires_at' => $userTelegramChat->token_expires_at->toIso8601String(),
        ]);
    }

    public function disconnectTelegram(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        $user->telegramChat()->update(['is_active' => false]);

        return to_route('integrations.edit')->with('status', 'telegram-disconnected');
    }
}
