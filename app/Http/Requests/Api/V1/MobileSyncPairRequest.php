<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MobileSyncPairRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:8'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }
}
