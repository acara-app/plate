<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AllergySeverity;
use App\Enums\UserProfileAttributeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @codeCoverageIgnore */
final class StoreDietaryPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attributes' => ['nullable', 'array'],
            'attributes.*.category' => ['required', Rule::enum(UserProfileAttributeCategory::class)],
            'attributes.*.value' => ['required', 'string', 'max:255'],
            'attributes.*.severity' => ['nullable', Rule::enum(AllergySeverity::class)],
            'attributes.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
