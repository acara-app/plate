<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserTelegramChat;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class IntegrationsController
{
    /**
     * Show the integrations settings page.
     */
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

    /**
     * Generate a new linking token for Telegram.
     */
    public function generateTelegramToken(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Deactivate any existing active links using the relationship
        $user->telegramChat()->active()->update(['is_active' => false]);

        // Create new link with token using the relationship
        $userTelegramChat = $user->telegramChat()->create([
            'is_active' => true,
        ]);

        $token = $userTelegramChat->generateToken();

        return to_route('integrations.edit')->with([
            'telegram_token' => $token,
            'token_expires_at' => $userTelegramChat->token_expires_at->toIso8601String(),
        ]);
    }

    /**
     * Disconnect Telegram integration.
     */
    public function disconnectTelegram(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Use the relationship to deactivate all links
        $user->telegramChat()->update(['is_active' => false]);

        return to_route('integrations.edit')->with('status', 'telegram-disconnected');
    }
}
