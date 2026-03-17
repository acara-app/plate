<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AgentMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::enum(AgentMode::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $conversationId = $this->route('conversationId');

            abort_if($conversationId && ! Str::isUuid($conversationId), 400, 'Invalid conversation ID format');
        });
    }
}
