<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @codeCoverageIgnore
 */
final class CaffeineAssessmentRequest extends FormRequest
{
    public const array ALLOWED_CONDITIONS = [
        'pregnancy',
        'breastfeeding',
        'trying_to_conceive',
        'heart_condition',
        'anxiety',
        'gerd',
        'insomnia',
        'medication',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'sex' => ['required', 'string', Rule::in(['male', 'female', 'decline'])],
            'sensitivity' => ['required', 'string', Rule::in(['low', 'normal', 'high'])],
            'context' => ['nullable', 'string', 'max:1000'],
            'conditions' => ['nullable', 'array', 'max:'.count(self::ALLOWED_CONDITIONS)],
            'conditions.*' => ['string', Rule::in(self::ALLOWED_CONDITIONS)],
            'locale' => ['nullable', 'string', Rule::in(['en', 'mn', 'fr'])],
        ];
    }

    public function weightKg(): float
    {
        $weightKg = $this->validated('weight_kg');

        assert(is_numeric($weightKg));

        return (float) $weightKg;
    }

    public function sex(): string
    {
        $sex = $this->validated('sex');

        assert(is_string($sex));

        return $sex;
    }

    public function sensitivity(): string
    {
        $sensitivity = $this->validated('sensitivity');

        assert(is_string($sensitivity));

        return $sensitivity;
    }

    public function context(): ?string
    {
        $context = $this->validated('context');

        return is_string($context) ? $context : null;
    }

    /**
     * @return array<int, string>
     */
    public function conditions(): array
    {
        $conditions = $this->validated('conditions');

        if (! is_array($conditions)) {
            return [];
        }

        return array_values(array_filter($conditions, is_string(...)));
    }

    public function locale(): string
    {
        $locale = $this->validated('locale');

        return is_string($locale) ? $locale : 'en';
    }
}
