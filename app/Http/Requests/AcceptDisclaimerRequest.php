<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** @codeCoverageIgnore */
final class AcceptDisclaimerRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'accepted' => ['required', 'accepted'],
            'health_data_consent' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'health_data_consent.required' => __('auth.register.health_data_consent_required'),
            'health_data_consent.accepted' => __('auth.register.health_data_consent_required'),
        ];
    }
}
