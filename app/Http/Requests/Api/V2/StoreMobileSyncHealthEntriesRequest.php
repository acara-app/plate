<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2;

use App\Rules\ValidTimezone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** @codeCoverageIgnore */
final class StoreMobileSyncHealthEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'device_identifier' => ['required', 'string'],
            'encrypted_payload' => ['required', 'string'],
            'timezone' => ['sometimes', 'string', 'max:255', new ValidTimezone],
        ];
    }
}
