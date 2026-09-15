<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class PhotoAnalysisContext
{
    public function __construct(
        public ?User $user,
        public ?string $guestId,
        public string $requestId,
        public string $source,
        public string $fingerprint,
    ) {}

    public static function guestId(Request $request): ?string
    {
        $guestId = $request->attributes->get('photo_guest_id');

        return is_string($guestId) ? $guestId : null;
    }

    public static function fromRequest(Request $request, string $source, string $image, ?User $user = null): self
    {
        $key = $request->header('Idempotency-Key') ?? $request->input('analysis_request_id') ?? (string) Str::uuid();

        if (! is_string($key) || ! Str::isUuid($key)) {
            throw ValidationException::withMessages(['analysis_request_id' => 'Use a UUID for the analysis request ID.']);
        }

        return new self(
            $user ?? $request->user(),
            self::guestId($request),
            $key,
            $source,
            hash('sha256', $image),
        );
    }
}
