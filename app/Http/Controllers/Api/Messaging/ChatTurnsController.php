<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Messaging;

use App\Actions\Messaging\DispatchChatTurnAction;
use App\Actions\Messaging\IssueLinkingCodeAction;
use App\Http\Requests\Api\Messaging\StoreChatTurnRequest;
use App\Models\UserChatPlatformLink;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChatTurnsController
{
    public function __construct(
        private DispatchChatTurnAction $dispatchChatTurn,
        private IssueLinkingCodeAction $issueLinkingCode,
    ) {}

    public function store(
        StoreChatTurnRequest $request,
        string $platform,
        string $platformUserId,
    ): JsonResponse {
        $link = UserChatPlatformLink::forUser($platform, $platformUserId)->first();

        if ($link === null || ! $link->isLinked()) {
            $issued = $this->issueLinkingCode->handle($platform, $platformUserId);

            return response()->json([
                'linking_code' => $issued['code'],
                'expires_at' => $issued['expires_at'],
            ], Response::HTTP_CONFLICT);
        }

        $result = $this->dispatchChatTurn->handle(
            $link,
            $request->string('message')->toString(),
        );

        return response()->json([
            'plate_user_id' => (string) $result['plate_user_id'],
            'conversation_id' => $result['conversation_id'],
            'response' => $result['response'],
        ], Response::HTTP_CREATED);
    }
}
