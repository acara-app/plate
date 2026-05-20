<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2;

use App\Http\Requests\StreamChatRequest;
use App\Models\User;

final class ChatStreamRequest extends StreamChatRequest
{
    public function authorize(): bool
    {
        $user = $this->user('sanctum');

        return $user instanceof User
            && $user->tokenCan('chat:converse');
    }
}
