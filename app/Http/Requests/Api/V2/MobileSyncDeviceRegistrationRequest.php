<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

final class MobileSyncDeviceRegistrationRequest extends FormRequest
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
            'device_name' => ['required', 'string', 'max:255'],
            'device_identifier' => ['required', 'string', 'max:255'],
        ];
    }
}
