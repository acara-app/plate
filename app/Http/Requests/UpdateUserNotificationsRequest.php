<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserNotificationsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'glucoseNotificationsEnabled' => ['required', 'boolean'],
            'glucoseNotificationLowThreshold' => ['nullable', 'integer', 'min:40', 'max:150'],
            'glucoseNotificationHighThreshold' => ['nullable', 'integer', 'min:100', 'max:400'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'glucoseNotificationsEnabled.required' => 'Notification preference is required.',
            'glucoseNotificationsEnabled.boolean' => 'Notification preference must be true or false.',
            'glucoseNotificationLowThreshold.integer' => 'Low threshold must be a number.',
            'glucoseNotificationLowThreshold.min' => 'Low threshold must be at least 40 mg/dL.',
            'glucoseNotificationLowThreshold.max' => 'Low threshold must not exceed 150 mg/dL.',
            'glucoseNotificationHighThreshold.integer' => 'High threshold must be a number.',
            'glucoseNotificationHighThreshold.min' => 'High threshold must be at least 100 mg/dL.',
            'glucoseNotificationHighThreshold.max' => 'High threshold must not exceed 400 mg/dL.',
        ];
    }
}
