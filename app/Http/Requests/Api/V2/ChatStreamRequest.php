<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2;

use App\Http\Requests\StreamChatRequest;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

final class ChatStreamRequest extends StreamChatRequest
{
    public function authorize(): bool
    {
        $user = $this->user('sanctum');

        if (! $user instanceof User || ! $user->tokenCan('chat:converse')) {
            return false; // @codeCoverageIgnore
        }

        if ($user->requiresConsent()) {
            throw new HttpResponseException(
                response()->json([
                    'code' => 'consent_required',
                    'message' => __('Please accept the medical disclaimer to continue.'),
                ], 403)
            );
        }

        return true;
    }
}
