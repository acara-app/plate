<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class RenderSnapBurstLimit
{
    public function __construct(private BuildSnapBurstLimit $buildSnapBurstLimit) {}

    public function handle(ThrottleRequestsException $throttleRequestsException, Request $request): JsonResponse|RedirectResponse|null
    {
        $isApi = $request->routeIs('api.v2.snap-to-track.analyze');

        if (! $isApi && ! $request->routeIs('snap-to-track.analyze')) {
            return null;
        }

        $retryAfter = $throttleRequestsException->getHeaders()['Retry-After'] ?? null;
        $burstLimit = $this->buildSnapBurstLimit->handle(
            $request,
            is_numeric($retryAfter) ? (int) $retryAfter : 3600,
        );

        if ($isApi) {
            return new JsonResponse(
                ['error' => 'burst_limit_exceeded', 'burstLimit' => $burstLimit->toArray()],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $burstLimit->retryAfterSeconds],
            );
        }

        Inertia::flash('analytics', [
            'name' => 'snap_to_track_limit_reached',
            'properties' => ['gate' => 'burst', 'tier' => $burstLimit->tier],
        ]);

        return back();
    }
}
