<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\User;
use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\TextStringObject;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\U2FPublicKey;

final class PasskeyAuthenticator
{
    private OpenSSLAsymmetricKey $key;

    private string $credentialId;

    private string $publicKey;

    public function __construct()
    {
        $this->key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $details = openssl_pkey_get_details($this->key)['ec'];
        $this->publicKey = U2FPublicKey::convertToCoseKey("\x04".$details['x'].$details['y']);
        $this->credentialId = random_bytes(32);
    }

    public function store(User $user): Passkey
    {
        $record = CredentialRecord::create(
            publicKeyCredentialId: $this->credentialId,
            type: 'public-key',
            transports: ['internal'],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $this->publicKey,
            userHandle: $user->getPasskeyUserHandle(),
            counter: 0,
        );

        return $user->passkeys()->create([
            'name' => 'My phone',
            'credential_id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'credential' => json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    /** @return array<string, mixed> */
    public function registration(string $challenge): array
    {
        $authData = hash('sha256', config('passkeys.relying_party_id'), binary: true)
            ."\x45".pack('N', 0).str_repeat("\0", 16)
            .pack('n', mb_strlen($this->credentialId, '8bit')).$this->credentialId.$this->publicKey;
        $attestation = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        return $this->credential([
            'clientDataJSON' => $this->clientData('webauthn.create', $challenge),
            'attestationObject' => Base64UrlSafe::encodeUnpadded((string) $attestation),
            'transports' => ['internal'],
        ]);
    }

    /** @return array<string, mixed> */
    public function assertion(string $challenge, User $user, ?string $origin = null, bool $userVerified = true): array
    {
        $clientData = $this->clientData('webauthn.get', $challenge, $origin);
        $authData = hash('sha256', config('passkeys.relying_party_id'), binary: true)
            .($userVerified ? "\x05" : "\x01").pack('N', 1);
        openssl_sign($authData.hash('sha256', Base64UrlSafe::decodeNoPadding($clientData), binary: true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $this->credential([
            'clientDataJSON' => $clientData,
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($authData),
            'signature' => Base64UrlSafe::encodeUnpadded($signature),
            'userHandle' => Base64UrlSafe::encodeUnpadded($user->getPasskeyUserHandle()),
        ]);
    }

    private function clientData(string $type, string $challenge, ?string $origin = null): string
    {
        return Base64UrlSafe::encodeUnpadded(json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => $origin ?? config('passkeys.allowed_origins')[0],
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function credential(array $response): array
    {
        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => $response,
            'clientExtensionResults' => [],
        ];
    }
}
