<?php

declare(strict_types=1);

namespace App\Utilities;

final readonly class Base64Image
{
    /**
     * @var list<string>
     */
    public const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private function __construct(
        public string $bytes,
        public string $mimeType,
    ) {}

    public static function decode(string $value): ?self
    {
        $payload = self::stripDataUrlPrefix(mb_trim($value));

        if ($payload === '') {
            return null;
        }

        $bytes = base64_decode($payload, true);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false) {
            return null;
        }

        $mimeType = $info['mime'];

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return null;
        }

        return new self($bytes, $mimeType);
    }

    public function base64(): string
    {
        return base64_encode($this->bytes);
    }

    public function byteLength(): int
    {
        return mb_strlen($this->bytes, '8bit');
    }

    private static function stripDataUrlPrefix(string $value): string
    {
        if (! str_starts_with($value, 'data:')) {
            return $value;
        }

        $separator = mb_strpos($value, ',');

        if ($separator === false) {
            return '';
        }

        return mb_substr($value, $separator + 1);
    }
}
