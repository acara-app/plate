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
            'height_cm' => ['required', 'integer', 'min:90', 'max:230'],
            'weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'age' => ['required', 'integer', 'min:13', 'max:120'],
            'sex' => ['required', 'string', Rule::in(['male', 'female', 'decline'])],
            'sensitivity' => ['required', 'string', Rule::in(['low', 'normal', 'high'])],
            'context' => ['nullable', 'string', 'max:1000'],
            'conditions' => ['nullable', 'array', 'max:'.count(self::ALLOWED_CONDITIONS)],
            'conditions.*' => ['string', Rule::in(self::ALLOWED_CONDITIONS)],
        ];
    }

    public function heightCm(): int
    {
        $heightCm = $this->validated('height_cm');

        assert(is_int($heightCm) || is_string($heightCm));

        return (int) $heightCm;
    }

    public function weightKg(): float
    {
        $weightKg = $this->validated('weight_kg');

        assert(is_numeric($weightKg));

        return (float) $weightKg;
    }

    public function age(): int
    {
        $age = $this->validated('age');

        assert(is_int($age) || is_string($age));

        return (int) $age;
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

        return is_array($conditions) ? $conditions : [];
    }
}
