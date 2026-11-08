<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** @codeCoverageIgnore */
final class DeleteUserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->confirmsWithEmail()) {
            return [
                'email' => ['required', 'string', Rule::in([mb_strtolower($this->account()->email)])],
            ];
        }

        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.in' => __('Type the email address of this account to confirm.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->confirmsWithEmail() && is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(mb_trim($this->input('email')))]);
        }
    }

    protected function passedValidation(): void
    {
        /** @var User $user */
        $user = $this->user();

        if ($user->hasActiveSubscription()) {
            throw ValidationException::withMessages([
                'subscription' => __('Please cancel your subscription before deleting your account.'),
            ]);
        }
    }

    private function confirmsWithEmail(): bool
    {
        return $this->account()->password === null;
    }

    private function account(): User
    {
        /** @var User */
        return $this->user();
    }
}
