<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class VerifySidecarSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('plate.sidecar.hmac_secret');

        throw_if($secret === '', RuntimeException::class, 'SIDECAR_HMAC_SECRET is not configured.');

        $signature = (string) $request->header('X-Sidecar-Signature', '');
        $timestamp = (string) $request->header('X-Sidecar-Timestamp', '');

        if ($signature === '' || $timestamp === '' || ! ctype_digit($timestamp)) {
            return $this->reject('missing_signature');
        }

        $skew = (int) config('plate.sidecar.clock_skew_seconds', 60);
        $delta = abs(time() - (int) $timestamp);
        if ($delta > $skew) {
            return $this->reject('stale_timestamp');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, mb_strtolower($signature))) {
            return $this->reject('invalid_signature');
        }

        return $next($request);
    }

    private function reject(string $reason): JsonResponse
    {
        return response()->json(['error' => $reason], Response::HTTP_UNAUTHORIZED);
    }
}
