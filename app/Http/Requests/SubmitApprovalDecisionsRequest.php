<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitApprovalDecisionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'decisions.*.result' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, array{action: string, result?: string|null}>
     */
    public function decisions(): array
    {
        /** @var array<string, array{action: string, result?: string|null}> $decisions */
        $decisions = $this->validated('decisions');

        return $decisions;
    }
}
