<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Messaging\DisconnectChatPlatformLink;
use App\Actions\Messaging\GenerateChatPlatformLinkToken;
use App\Data\Messaging\ChatPlatformIntegrationData;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IntegrationsController
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $links = $user->chatPlatformLinks()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (UserChatPlatformLink $link) => $link->platform->value);

        $platforms = collect(ChatPlatform::cases())
            ->map(fn (ChatPlatform $platform): ChatPlatformIntegrationData => ChatPlatformIntegrationData::fromLink(
                $platform,
                $links->get($platform->value),
            ))
            ->values();

        return Inertia::render('integrations/edit', [
            'platforms' => $platforms,
        ]);
    }

    public function connect(
        Request $request,
        ChatPlatform $platform,
        GenerateChatPlatformLinkToken $generateToken,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $result = $generateToken->handle($user, $platform);

        return to_route('integrations.edit')->with([
            'linking_platform' => $platform->value,
            'linking_token' => $result['token'],
            'token_expires_at' => $result['link']->token_expires_at?->toIso8601String(),
        ]);
    }

    public function disconnect(
        Request $request,
        ChatPlatform $platform,
        DisconnectChatPlatformLink $disconnect,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $disconnect->handle($user, $platform);

        return to_route('integrations.edit')->with('status', $platform->value.'-disconnected');
    }
}
