<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2\Account;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptConsentRequest extends FormRequest
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
            'terms_version' => ['required', 'string', 'max:50'],
            'privacy_version' => ['required', 'string', 'max:50'],
            'medical_disclaimer' => ['required', 'accepted'],
        ];
    }
}
