<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreMobileSyncHealthEntriesRequest extends FormRequest
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
            'device_identifier' => ['required', 'string'],
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.type' => ['required', 'string', 'max:100'],
            'entries.*.value' => ['required', 'numeric'],
            'entries.*.unit' => ['required', 'string', 'max:20'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.source' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $deviceIdentifier = $this->input('device_identifier');

            if ($deviceIdentifier === null) {
                return;
            }

            /** @var User $user */
            $user = $this->user();

            $deviceExists = MobileSyncDevice::query()
                ->where('user_id', $user->id)
                ->where('device_identifier', $deviceIdentifier)
                ->where('is_active', true)
                ->exists();

            if (! $deviceExists) {
                $validator->errors()->add(
                    'device_identifier',
                    'The selected device identifier is invalid or does not belong to you.'
                );
            }
        });
    }
}
