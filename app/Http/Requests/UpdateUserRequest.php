<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @codeCoverageIgnore */
final class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        assert($user instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'locale' => [
                'nullable',
                'string',
                Rule::in(LanguageUtil::keys()),
            ],
        ];
    }
}
