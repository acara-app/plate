<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Messaging;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreChatTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'platform_message_id' => ['nullable', 'string', 'max:255'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.url' => ['required_with:attachments', 'string', 'url'],
            'attachments.*.media_type' => ['nullable', 'string', 'max:255'],
            'attachments.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
