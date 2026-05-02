<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Services\StripeServiceContract;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    private readonly StripeServiceContract $stripe;

    public function __construct(?StripeServiceContract $stripe = null)
    {
        $this->stripe = $stripe ?? resolve(StripeServiceContract::class);
    }

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // @codeCoverageIgnoreStart
        return parent::version($request);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = $user instanceof User ? ($user->locale ?? 'en') : 'en';

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'subscribed' => $user?->hasActiveSubscription() ?? false,
            ],
            'enablePremiumUpgrades' => enable_premium_upgrades_for($user instanceof User ? $user : null),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $locale,
            'availableLanguages' => LanguageUtil::all(),
            'translations' => Inertia::once(fn (): array => LanguageUtil::translations($locale)),
            'creditWarning' => $request->hasSession() ? $request->session()->get('credit_warning') : null,
            'entitlement' => $this->buildEntitlement($user),
        ];
    }

    /**
     * @return array{
     *     tier: string,
     *     tier_label: string,
     *     payment_pending: bool,
     *     payment_recovery_url: ?string,
     *     premium_enforcement_active: bool,
     *     on_grace_period: bool,
     *     grace_period_ends_at: ?string
     * }|null
     */
    private function buildEntitlement(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $entitlement = resolve(ResolvesUserTier::class)->resolve($user);
        $isPaymentPending = $entitlement->isPaymentPending();

        return [
            'tier' => $entitlement->tier->value,
            'tier_label' => $entitlement->tier->label(),
            'payment_pending' => $isPaymentPending,
            'payment_recovery_url' => $isPaymentPending
                ? $this->stripe->getIncompletePaymentUrlForUser($user)
                : null,
            'premium_enforcement_active' => $entitlement->premiumEnforcementActive,
            'on_grace_period' => $entitlement->inGracePeriod(),
            'grace_period_ends_at' => $entitlement->gracePeriodEndsAt instanceof CarbonInterface
                ? $entitlement->gracePeriodEndsAt->toIso8601String()
                : null,
        ];
    }
}
