<?php

declare(strict_types=1);

namespace App\Http\Requests\SnapToTrack;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSnapToTrackPhotoRequest extends FormRequest
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
            'photo' => ['required', 'image', 'max:10240'],
        ];
    }
}
