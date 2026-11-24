<?php

declare(strict_types=1);

use App\Actions\Billing\ClaimPlanPurchase;
use App\Actions\Billing\ResolveGooglePurchaseAccount;
use App\Actions\CreateUser;
use App\Actions\RecordSignUpAgreements;
use App\Actions\ResolvePendingMeal;
use App\Actions\SetFirstPassword;
use App\Enums\PlanPurchaseClaim;
use App\Exceptions\AccountLinkException;
use App\Jobs\SendPlanPurchaseClaimLink;
use App\Models\PlanPurchase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['bodyClass' => 'bg-muted text-foreground', 'viewport' => 'width=device-width, initial-scale=1, viewport-fit=cover', 'themeColor' => '#f5f5f5', 'metaDescription' => 'Activate your Snap to Track Starter plan.'])]
#[Title('Activate Starter | Snap to Track')]
class extends Component
{
    #[Locked]
    public ?string $purchaseId = null;

    #[Locked]
    public bool $googleClaim = false;

    #[Locked]
    public ?string $tokenHash = null;

    #[Locked]
    public ?string $outcome = null;

    #[Locked]
    public bool $linkResent = false;

    public bool $acceptedDisclaimer = false;

    public bool $healthDataConsent = false;

    public string $password = '';

    #[Locked]
    public bool $passwordSaved = false;

    public function mount(): void
    {
        if (request()->query('method') === 'google') {
            $this->googleClaim = true;
            $purchaseId = session('snap_to_track.purchase');
            $this->purchaseId = is_string($purchaseId) ? $purchaseId : null;
            $purchase = $this->purchase;

            if (! $purchase instanceof PlanPurchase || ! $purchase->isPaid() || $purchase->isCancelled() || $purchase->isClaimed()) {
                $this->redirect(route('snap-to-track.purchase'));

                return;
            }

            $this->activateWithGoogle($purchase, acceptedAgreements: false);

            return;
        }

        $claim = session('snap_to_track.claim');
        $this->purchaseId = is_array($claim) && is_string($claim['purchase'] ?? null) ? $claim['purchase'] : null;
        $this->tokenHash = is_array($claim) && is_string($claim['token_hash'] ?? null) ? $claim['token_hash'] : null;
    }

    #[Computed]
    public function purchase(): ?PlanPurchase
    {
        return $this->purchaseId === null ? null : PlanPurchase::query()->with('product')->find($this->purchaseId);
    }

    #[Computed]
    public function state(): string
    {
        $purchase = $this->purchase;

        return $this->outcome ?? match (true) {
            ! $purchase instanceof PlanPurchase || ! $purchase->isPaid() => 'invalid',
            $purchase->isClaimed() => auth()->check() && $purchase->user_id === auth()->id() ? PlanPurchaseClaim::Claimed->value : PlanPurchaseClaim::ClaimedElsewhere->value,
            $purchase->isCancelled() => PlanPurchaseClaim::Cancelled->value,
            $this->googleClaim => $this->googleProof() === null ? 'invalid' : 'new_account',
            $this->tokenHash === null || $purchase->claim_token_hash !== $this->tokenHash => 'invalid',
            $purchase->claimLinkExpired() => 'expired',
            auth()->check() => 'confirm',
            $this->hasAccount($purchase) => 'log_in',
            default => 'new_account',
        };
    }

    #[Computed]
    public function canSetPassword(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $this->purchase?->user_id === $user->id
            && $user->password === null && $user->google_id === null && $user->apple_id === null;
    }

    #[Computed]
    public function mealSaveUrl(): ?string
    {
        $token = session('snap_to_track.result.draft');
        $user = auth()->user();

        return is_string($token) && $user instanceof User ? resolve(ResolvePendingMeal::class)->handle($token, $user)?->saveUrl : null;
    }

    public function activate(ClaimPlanPurchase $claimPurchase, CreateUser $createUser, RecordSignUpAgreements $recordAgreements): void
    {
        $state = $this->state;
        $purchase = $this->purchase;

        if (! in_array($state, ['new_account', 'confirm'], true) || ! $purchase instanceof PlanPurchase || $purchase->email === null) {
            return;
        }

        if ($state === 'new_account') {
            $this->validate(
                ['acceptedDisclaimer' => ['accepted'], 'healthDataConsent' => ['accepted']],
                ['acceptedDisclaimer.accepted' => __('auth.register.agreements_missing'), 'healthDataConsent.accepted' => __('auth.register.health_data_consent_required')],
            );

            if ($this->googleClaim) {
                $this->activateWithGoogle($purchase, acceptedAgreements: true);

                return;
            }

            $user = DB::transaction(function () use ($createUser, $recordAgreements, $purchase): User {
                $user = $createUser->handle(['email' => (string) $purchase->email, 'email_verified_at' => now()], null);
                $recordAgreements->handle($user);

                return $user;
            });

            Auth::login($user, remember: true);
            session()->regenerate();
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->claim($claimPurchase, $purchase, $user, 'email_link');
    }

    /** @return array{email: string, google_id: string, name: string|null}|null */
    private function googleProof(): ?array
    {
        $proof = session('snap_to_track.google_claim');

        if ($this->purchaseId === null || session('snap_to_track.purchase') !== $this->purchaseId
            || ! is_array($proof) || ($proof['purchase'] ?? null) !== $this->purchaseId
            || ! is_string($proof['email'] ?? null) || ! is_string($proof['google_id'] ?? null)) {
            return null;
        }

        return ['email' => $proof['email'], 'google_id' => $proof['google_id'], 'name' => $proof['name'] ?? null];
    }

    private function activateWithGoogle(PlanPurchase $purchase, bool $acceptedAgreements): void
    {
        $proof = $this->googleProof();

        if ($proof === null) {
            $this->redirect(route('snap-to-track.purchase'));

            return;
        }

        if ($purchase->email === null || Str::lower($proof['email']) !== Str::lower($purchase->email)) {
            $this->returnToPurchase("That Google account doesn't match the email used at checkout.");

            return;
        }

        try {
            $user = resolve(ResolveGooglePurchaseAccount::class)->handle($proof['email'], $proof['google_id'], $proof['name'], $acceptedAgreements);
        } catch (AccountLinkException $exception) {
            $this->returnToPurchase($exception->getMessage());

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->returnToPurchase('We could not activate your plan just now. Please try again.');

            return;
        }

        if ($user instanceof User) {
            if (Auth::check() && Auth::id() !== $user->id) {
                Auth::guard('web')->logoutCurrentDevice();
                session()->invalidate();
                session()->put('snap_to_track.purchase', $purchase->id);
            }

            Auth::login($user, remember: true);
            $this->claim(resolve(ClaimPlanPurchase::class), $purchase, $user, 'google');
        }
    }

    private function returnToPurchase(string $reason): void
    {
        session()->forget('snap_to_track.google_claim');
        session()->flash('snap_to_track.google_error', $reason);
        $this->redirect(route('snap-to-track.purchase'));
    }

    private function claim(ClaimPlanPurchase $claimPurchase, PlanPurchase $purchase, User $user, string $method): void
    {
        try {
            $outcome = $claimPurchase->handle($purchase, $user, $method);
        } catch (Throwable $exception) {
            report($exception);

            if ($this->googleClaim) {
                $this->returnToPurchase('We could not activate your plan just now. Please try again.');
            } else {
                $this->addError('activate', 'We could not activate your plan just now. Please try again.');
            }

            return;
        }

        if ($this->googleClaim) {
            session()->forget('snap_to_track.google_claim');
        }

        $this->outcome = $outcome->value;
        unset($this->purchase, $this->state, $this->mealSaveUrl, $this->canSetPassword);

        if ($outcome === PlanPurchaseClaim::Claimed && $this->mealSaveUrl === null && ! $this->canSetPassword) {
            $this->redirect(route('snap-to-track.index'));
        }
    }

    public function setPassword(SetFirstPassword $setFirstPassword): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $this->canSetPassword) {
            return;
        }

        $this->validate(['password' => ['required', 'string', Password::defaults()]]);

        $saved = $setFirstPassword->handle($user, $this->password);
        $this->password = '';

        if (! $saved) {
            $this->addError('password', 'This account already has a password. Use that one to log in on other devices.');

            return;
        }

        Auth::login($user, remember: true);
        $this->passwordSaved = true;
        unset($this->canSetPassword);
    }

    public function resendLink(): void
    {
        $purchase = $this->purchase;

        if ($this->state !== 'expired' || ! $purchase instanceof PlanPurchase) {
            return;
        }

        $this->linkResent = RateLimiter::attempt('plan-purchase-link:'.$purchase->id, 3, fn (): bool => (bool) dispatch(new SendPlanPurchaseClaimLink($purchase->id)), 600) !== false;
    }

    public function logIn(): void
    {
        session()->put('url.intended', route('snap-to-track.activate', absolute: false));

        $this->redirect(route('login'));
    }

    public function useAnotherAccount(): void
    {
        $claim = session('snap_to_track.claim');

        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->put('snap_to_track.claim', $claim);

        $this->redirect(route('snap-to-track.activate'));
    }

    private function hasAccount(PlanPurchase $purchase): bool
    {
        return $purchase->email !== null && User::query()->whereRaw('lower(email) = ?', [Str::lower($purchase->email)])->exists();
    }
};
?>

@php
    $purchase = $this->purchase;
    $state = $this->state;
    $plan = $purchase?->product->name ?? 'Starter';
    $gutter = 'pr-[max(1rem,env(safe-area-inset-right))] pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1.5rem,env(safe-area-inset-right))] sm:pl-[max(1.5rem,env(safe-area-inset-left))]';
    $press = 'touch-manipulation select-none [-webkit-touch-callout:none] transition duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100';
    $ring = 'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';
    $legalLink = 'underline underline-offset-4 hover:text-foreground';
    $consent = 'flex items-start gap-4 rounded-3xl bg-muted p-4 touch-manipulation select-none transition duration-150 ease-out has-checked:bg-primary/10 active:scale-[0.985] motion-reduce:transition-none motion-reduce:active:scale-100';
    $supportUrl = $purchase instanceof PlanPurchase && in_array($state, ['active_plan', 'other_customer'], true)
        ? App\Utilities\SupportEmailLink::make($plan.' purchase needs help ('.$purchase->reference().')', [
            $state === 'active_plan' ? 'I bought '.$plan.', but my account already has an active plan.' : 'I bought '.$plan.', but it could not be added to my account automatically.',
            '',
            'Purchase reference: '.$purchase->reference(),
            'Account email: '.auth()->user()?->email,
        ])
        : App\Utilities\SupportEmailLink::make($plan.' activation link does not work', [
            'My '.$plan.' activation link does not work.',
            '',
            ...($purchase instanceof PlanPurchase ? ['Purchase reference: '.$purchase->reference()] : []),
            'Email I used at checkout: ',
        ]);
@endphp

<div class="min-h-svh bg-muted text-foreground [-webkit-tap-highlight-color:transparent] [-webkit-text-size-adjust:100%]">
    <header class="{{ $gutter }} pt-[env(safe-area-inset-top)]">
        <div class="mx-auto flex h-14 w-full max-w-md items-center">
            <a href="{{ route('home') }}" aria-label="Acara Plate home" class="-ml-2 inline-flex min-h-11 items-center gap-2 rounded-full px-2 {{ $press }} {{ $ring }}">
                <span aria-hidden="true" class="text-xl leading-none">🍓</span>
                <span class="text-[17px] font-bold tracking-tight">Acara Plate</span>
            </a>
        </div>
    </header>

    <main class="{{ $gutter }} pt-2 pb-[calc(env(safe-area-inset-bottom)+2rem)] sm:pt-6">
        <section class="mx-auto flex w-full max-w-md flex-col gap-4 rounded-[1.75rem] bg-card p-6">
            @if ($state === 'new_account')
                <p class="self-start rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary">Payment received</p>
                <h1 class="text-2xl font-semibold tracking-tight">Activate {{ $plan }}</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Your plan is paid. We'll set up your free Acara Plate account for <span class="font-medium wrap-anywhere text-foreground">{{ $purchase->email }}</span> so you can keep logging meals from photos.
                </p>
                <div class="flex flex-col gap-3">
                    <label for="accepted_disclaimer" class="{{ $consent }}">
                        <span class="min-w-0 flex-1">
                            <span class="block text-[15px] font-bold">{{ __('auth.register.disclaimer_title') }}</span>
                            <span class="mt-1 block text-[13px] leading-snug text-muted-foreground">
                                <span class="block">{{ __('auth.register.disclaimer_acceptance') }}</span>
                                <span class="mt-1 block">
                                    {{ __('auth.register.disclaimer_terms_prefix') }}
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="{{ $legalLink }}">{{ __('auth.register.terms_of_service') }}</a>
                                    {{ __('auth.register.and') }}
                                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="{{ $legalLink }}">{{ __('auth.register.privacy_policy') }}</a>.
                                </span>
                            </span>
                        </span>
                        <input id="accepted_disclaimer" type="checkbox" wire:model="acceptedDisclaimer" class="mt-1 size-5 shrink-0 accent-primary" />
                    </label>
                    <label for="health_data_consent" class="{{ $consent }}">
                        <span class="min-w-0 flex-1">
                            <span class="block text-[15px] font-bold">{{ __('auth.register.health_data_consent_title') }}</span>
                            <span class="mt-1 block text-[13px] leading-snug text-muted-foreground">{{ __('auth.register.health_data_consent') }}</span>
                        </span>
                        <input id="health_data_consent" type="checkbox" wire:model="healthDataConsent" class="mt-1 size-5 shrink-0 accent-primary" />
                    </label>
                    @if ($errors->hasAny(['acceptedDisclaimer', 'healthDataConsent', 'activate']))
                        <p role="alert" class="text-sm text-destructive">{{ $errors->first('acceptedDisclaimer') ?: ($errors->first('healthDataConsent') ?: $errors->first('activate')) }}</p>
                    @endif
                    <x-snap-result.button wire:click="activate" wire:loading.attr="disabled" wire:target="activate">Activate {{ $plan }}</x-snap-result.button>
                </div>
                @unless ($googleClaim)
                    <p class="text-xs leading-relaxed text-muted-foreground">No password needed now. You can set one after activating.</p>
                @endunless
            @elseif ($state === 'confirm')
                <h1 class="text-2xl font-semibold tracking-tight">Add {{ $plan }} to your account</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ $plan }} will be added to <span class="font-medium wrap-anywhere text-foreground">{{ auth()->user()?->email }}</span>.
                </p>
                @error('activate')
                    <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                @enderror
                <x-snap-result.button wire:click="activate" wire:loading.attr="disabled" wire:target="activate">Add {{ $plan }}</x-snap-result.button>
                <button type="button" wire:click="useAnotherAccount" class="inline-flex min-h-11 items-center justify-center self-center rounded-full px-3 text-sm font-medium text-primary {{ $press }} {{ $ring }}">
                    Not you? Use another account
                </button>
            @elseif ($state === 'log_in')
                <h1 class="text-2xl font-semibold tracking-tight">Log in to add {{ $plan }}</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    <span class="font-medium wrap-anywhere text-foreground">{{ $purchase->email }}</span> already has an Acara Plate account. Log in and we'll add {{ $plan }} to it.
                </p>
                <x-snap-result.button wire:click="logIn">Log in</x-snap-result.button>
            @elseif ($state === 'claimed')
                <p class="self-start rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary">{{ $plan }} is active</p>
                <h1 class="text-2xl font-semibold tracking-tight">You're all set</h1>
                @if ($this->mealSaveUrl !== null)
                    <p class="text-sm leading-relaxed text-muted-foreground">Want to keep the meal you just scanned? It goes straight into your food log.</p>
                    <x-snap-result.button :href="$this->mealSaveUrl">Save this meal</x-snap-result.button>
                    <x-snap-result.button variant="plain" class="bg-muted" :href="route('snap-to-track.index')">
                        <x-snap-result.icon name="camera" />
                        Scan a new meal
                    </x-snap-result.button>
                @else
                    <x-snap-result.button :href="route('snap-to-track.index')">
                        <x-snap-result.icon name="camera" />
                        Scan a meal
                    </x-snap-result.button>
                @endif
                @if ($passwordSaved)
                    <p role="status" class="border-t border-muted pt-4 text-sm leading-relaxed text-muted-foreground">
                        Password saved. Log in with <span class="font-medium wrap-anywhere text-foreground">{{ auth()->user()?->email }}</span> and this password on any device.
                    </p>
                @elseif ($this->canSetPassword)
                    <div x-data="{ open: @js($errors->has('password')) }" class="flex flex-col gap-3 border-t border-muted pt-4">
                        <p class="text-sm leading-relaxed text-muted-foreground">Want to log in on other devices? Add a password to your account.</p>
                        <button type="button" x-show="! open" x-on:click="open = true; $nextTick(() => $refs.password.focus())" class="inline-flex min-h-11 items-center self-start rounded-full text-sm font-medium text-primary {{ $press }} {{ $ring }}">
                            Set a password
                        </button>
                        <div x-show="open" @style(['display: none' => ! $errors->has('password')]) class="flex flex-col gap-2">
                            <label for="new_password" class="text-sm font-medium">New password</label>
                            <input
                                id="new_password"
                                x-ref="password"
                                type="password"
                                wire:model="password"
                                autocomplete="new-password"
                                enterkeyhint="done"
                                wire:keydown.enter="setPassword"
                                class="min-h-12 w-full rounded-2xl border border-input bg-background px-4 text-base outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            @error('password')
                                <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                            @else
                                <p class="text-xs text-muted-foreground">At least 12 characters. A short sentence works well.</p>
                            @enderror
                            <x-snap-result.button variant="plain" class="mt-1 bg-muted" wire:click="setPassword" wire:loading.attr="disabled" wire:target="setPassword">Save password</x-snap-result.button>
                        </div>
                    </div>
                @endif
            @elseif ($state === 'active_plan' || $state === 'other_customer')
                <h1 class="text-2xl font-semibold tracking-tight">Let's sort this out together</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ $state === 'active_plan' ? 'Your account already has an active plan, so we did not add this purchase to it.' : 'Your account already has billing details with us, so we could not add this purchase automatically.' }}
                    Your payment is safe. Email us with reference {{ $purchase?->reference() }} and we'll take care of it. Your email link keeps working until it expires.
                </p>
                <x-snap-result.button :href="$supportUrl">Email support@acara.app</x-snap-result.button>
            @elseif ($state === 'claimed_elsewhere')
                <h1 class="text-2xl font-semibold tracking-tight">{{ $plan }} is already active</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">This purchase was added to an account. Log in to that account to use it.</p>
                <x-snap-result.button :href="route('login')">Log in</x-snap-result.button>
            @elseif ($state === 'cancelled')
                <h1 class="text-2xl font-semibold tracking-tight">This purchase was cancelled</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">It is no longer active, and nothing more will be charged.</p>
                <x-snap-result.button :href="route('snap-to-track')">Back to Snap to Track</x-snap-result.button>
            @elseif ($state === 'expired')
                @if ($linkResent)
                    <h1 class="text-2xl font-semibold tracking-tight">Check your inbox</h1>
                    <p role="status" class="text-sm leading-relaxed text-muted-foreground">We sent a fresh link to <span class="font-medium wrap-anywhere text-foreground">{{ $purchase->maskedEmail() }}</span>. It can take a minute to arrive, so check your spam folder if it isn't in your inbox.</p>
                @else
                    <h1 class="text-2xl font-semibold tracking-tight">This link has expired</h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        For your security, activation links expire after {{ PlanPurchase::CLAIM_LINK_MINUTES }} minutes. We can send a fresh one to <span class="font-medium wrap-anywhere text-foreground">{{ $purchase->maskedEmail() }}</span>.
                    </p>
                    <x-snap-result.button wire:click="resendLink" wire:loading.attr="disabled" wire:target="resendLink">Email me a new link</x-snap-result.button>
                @endif
            @else
                <h1 class="text-2xl font-semibold tracking-tight">This link is no longer valid</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Activation links work once. Please use the newest email we sent you, or write to <a href="{{ $supportUrl }}" class="font-medium text-primary underline underline-offset-4">support@acara.app</a> if you need a hand.
                </p>
                <x-snap-result.button variant="plain" class="bg-muted" :href="route('snap-to-track')">Back to Snap to Track</x-snap-result.button>
            @endif
        </section>
    </main>
</div>
