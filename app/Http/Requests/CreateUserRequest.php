<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** @codeCoverageIgnore */
final class CreateUserRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                Password::defaults(),
            ],
            'accepted_disclaimer' => ['required', 'accepted'],
            'health_data_consent' => ['required', 'accepted'],
            'improve_food_recognition' => ['nullable', 'boolean'],
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
