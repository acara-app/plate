<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailIsVerifiedOrSubscribed
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->canUseApp()) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, 'Your email address is not verified.');

        return Redirect::guest(route('verification.notice'));
    }
}
