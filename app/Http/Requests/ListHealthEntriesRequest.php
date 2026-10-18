<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HealthEntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListHealthEntriesRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(HealthEntryType::class)],
        ];
    }
}
