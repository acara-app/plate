<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireSubscription
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $requiresSubscription = $this->requiresSubscription($user);

        $request->attributes->set('requiresSubscription', $requiresSubscription);

        return $next($request);
    }

    private function requiresSubscription(User $user): bool
    {
        return enable_premium_upgrades() && ! $user->is_verified;
    }
}
