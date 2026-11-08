<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsentSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserPrivacyRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'improve_food_recognition' => ['required', 'boolean'],
            'source' => ['nullable', Rule::enum(ConsentSource::class)->only([ConsentSource::Settings, ConsentSource::MealCorrection])],
        ];
    }

    public function source(): ConsentSource
    {
        return $this->enum('source', ConsentSource::class) ?? ConsentSource::Settings;
    }
}
