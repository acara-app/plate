<?php

declare(strict_types=1);

namespace App\Rules;

use App\Utilities\Base64Image;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class ValidBase64Image implements ValidationRule
{
    public function __construct(private int $maxKilobytes) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute field must be an image.');

            return;
        }

        $image = Base64Image::decode($value);

        if (! $image instanceof Base64Image) {
            $fail('The :attribute field must be an image.');

            return;
        }

        if ($image->byteLength() > $this->maxKilobytes * 1024) {
            $fail('The :attribute field must not be greater than :max kilobytes.')
                ->translate(['max' => $this->maxKilobytes]);
        }
    }
}
