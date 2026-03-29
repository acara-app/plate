<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class DeleteUserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
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
}
