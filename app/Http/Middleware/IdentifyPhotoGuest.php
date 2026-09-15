<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Billing\ManagesPhotoAnalyses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdentifyPhotoGuest
{
    public function __construct(private ManagesPhotoAnalyses $access) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->access->enabled()) {
            $id = $request->cookie('photo_guest');
            if (! is_string($id) || ! Str::isUuid($id)) {
                $id = (string) Str::uuid();
                Cookie::queue(cookie('photo_guest', $id, 525600, secure: $request->isSecure(), httpOnly: true, sameSite: 'lax'));
            }

            $request->attributes->set('photo_guest_id', $id);
        }

        return $next($request);
    }
}
