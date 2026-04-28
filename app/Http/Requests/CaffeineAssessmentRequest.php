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
            'sensitivity' => ['required', 'string', Rule::in(['low', 'normal', 'high'])],
            'context' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function heightCm(): int
    {
        $heightCm = $this->validated('height_cm');

        assert(is_int($heightCm) || is_string($heightCm));

        return (int) $heightCm;
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
}
