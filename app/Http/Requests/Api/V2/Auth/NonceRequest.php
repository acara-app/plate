<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** @codeCoverageIgnore */
final class NonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'device_identifier' => ['required', 'string', 'max:255'],
        ];
    }
}
