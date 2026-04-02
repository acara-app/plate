<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidTimezone;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTimezoneRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['required', 'string', 'max:255', new ValidTimezone],
        ];
    }
}
