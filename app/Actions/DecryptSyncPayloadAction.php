<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Validator;

final readonly class DecryptSyncPayloadAction
{
    /**
     * @return array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null, metadata?: array<string, string>|null}>
     */
    public function handle(string $base64Payload, string $base64Key): array
    {
        $payload = base64_decode($base64Payload, true);

        abort_if($payload === false || mb_strlen($payload, '8bit') < 28, 422, 'Invalid encrypted payload.');

        $key = base64_decode($base64Key, true);

        abort_if($key === false || mb_strlen($key, '8bit') !== 32, 500, 'Device encryption key is corrupted.');

        $nonce = mb_substr($payload, 0, 12, '8bit');
        $tag = mb_substr($payload, -16, null, '8bit');
        $ciphertext = mb_substr($payload, 12, -16, '8bit');

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        abort_if($decrypted === false, 422, 'Failed to decrypt payload. The encryption key may be out of sync — please re-pair the device.');

        $data = json_decode($decrypted, true);

        abort_unless(is_array($data), 422, 'Decrypted payload has an invalid structure.');

        $validated = Validator::make($data, [
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.type' => ['required', 'string', 'max:100'],
            'entries.*.value' => ['required', 'numeric'],
            'entries.*.unit' => ['required', 'string', 'max:20'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.source' => ['nullable', 'string', 'max:100'],
            'entries.*.metadata' => ['nullable', 'array'],
            'entries.*.metadata.*' => ['nullable', 'string', 'max:500'],
        ])->validate();

        /** @var array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null, metadata?: array<string, string>|null}> $entries */
        $entries = $validated['entries'];

        return $entries;
    }
}
