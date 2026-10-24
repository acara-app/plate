<?php

declare(strict_types=1);

namespace App\Data;

use Laravel\Passkeys\Passkey;
use Spatie\LaravelData\Data;

final class UserPasskeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $authenticator,
        public ?string $created_at,
        public ?string $last_used_at,
    ) {}

    public static function fromPasskey(Passkey $passkey): self
    {
        return new self(
            id: $passkey->id,
            name: $passkey->name,
            authenticator: $passkey->authenticator,
            created_at: $passkey->created_at?->toIso8601String(),
            last_used_at: $passkey->last_used_at?->toIso8601String(),
        );
    }
}
