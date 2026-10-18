<?php

declare(strict_types=1);

namespace App\Http\Requests\SnapToTrack;

use Illuminate\Foundation\Http\FormRequest;

final class EstimateSnapToTrackItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'grams' => ['nullable', 'numeric', 'min:1', 'max:2000'],
        ];
    }

    public function foodName(): string
    {
        return $this->string('name')->trim()->toString();
    }

    public function grams(): ?float
    {
        return $this->filled('grams') ? $this->float('grams') : null;
    }
}
