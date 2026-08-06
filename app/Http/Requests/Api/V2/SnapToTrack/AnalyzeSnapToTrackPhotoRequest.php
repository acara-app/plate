<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2\SnapToTrack;

use App\Rules\ValidBase64Image;
use App\Utilities\Base64Image;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

final class AnalyzeSnapToTrackPhotoRequest extends FormRequest
{
    private const int MAX_KILOBYTES = 10240;

    private ?Base64Image $decoded = null;

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
            'photo' => ['required', 'string', new ValidBase64Image(self::MAX_KILOBYTES)],
        ];
    }

    public function decodedImage(): Base64Image
    {
        if ($this->decoded instanceof Base64Image) {
            return $this->decoded;
        }

        $decoded = Base64Image::decode($this->string('photo')->toString());

        if (! $decoded instanceof Base64Image) {
            throw new RuntimeException('Photo failed to decode after validation.');
        }

        return $this->decoded = $decoded;
    }
}
