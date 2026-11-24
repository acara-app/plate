<?php

declare(strict_types=1);

use App\Actions\Billing\StartGuestCheckout;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\SnapFunnelStage;
use App\Jobs\SendPlanPurchaseClaimLink;
use App\Models\PlanPurchase;
use App\Services\Billing\SnapFunnel;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['bodyClass' => 'bg-muted text-foreground', 'viewport' => 'width=device-width, initial-scale=1, viewport-fit=cover', 'themeColor' => '#f5f5f5', 'metaDescription' => 'Your Snap to Track Starter checkout.'])]
#[Title('Your Starter plan | Snap to Track')]
class extends Component
{
    #[Locked]
    public ?string $purchaseId = null;

    #[Locked]
    public int $confirmUntil = 0;

    #[Locked]
    public bool $linkResent = false;

    public function mount(SnapFunnel $funnel): void
    {
        $purchaseId = session('snap_to_track.purchase');
        $this->purchaseId = is_string($purchaseId) ? $purchaseId : null;
        $purchase = $this->purchase;

        if (! $purchase instanceof PlanPurchase) {
            return;
        }

        if (request()->query('checkout') === 'cancelled') {
            $funnel->record(SnapFunnelStage::CheckoutCancelled, $purchase->id);
        }

        if (request()->query('checkout') === 'success') {
            $purchase->checkout_returned_at ??= now();
            $purchase->save();
            $funnel->record(SnapFunnelStage::CheckoutReturned, $purchase->id, ['entitlement' => $purchase->isPaid() ? 'active' : 'pending']);
        }

        if ($this->hasPendingGoogleClaim() || request()->query('checkout') === 'success') {
            $this->confirmUntil = now()->addMinute()->getTimestamp();
        }
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

        return match (true) {
            ! $purchase instanceof PlanPurchase => 'missing',
            $purchase->isCancelled() => 'cancelled',
            $purchase->isClaimed() => 'claimed',
            $purchase->isPaid() => 'paid',
            $purchase->checkout_returned_at !== null || $this->hasPendingGoogleClaim() => 'confirming',
            default => 'open',
        };
    }

    public function continueCheckout(StartGuestCheckout $startCheckout): void
    {
        $guestId = PhotoAnalysisContext::guestId(request()) ?? session('snap_to_track.analysis_guest_id');
        $purchase = $this->purchase;

        if (! is_string($guestId) || ! $purchase instanceof PlanPurchase) {
            $this->redirect(route('snap-to-track'));

            return;
        }

        try {
            $this->redirect($startCheckout->handle($guestId, $purchase->placement));
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('checkout', 'We could not open checkout just now. Please try again.');
        }
    }

    public function checkPaymentStatus(): void
    {
        $this->confirmUntil = now()->addMinute()->getTimestamp();
        $this->pollPaymentStatus();
    }

    public function pollPaymentStatus(): void
    {
        unset($this->purchase, $this->state);

        if ($this->hasPendingGoogleClaim() && $this->state === 'paid') {
            $this->redirect(route('snap-to-track.activate', ['method' => 'google']));
        }
    }

    public function resendLink(): void
    {
        $purchase = $this->purchase;

        if ($this->state !== 'paid' || ! $purchase instanceof PlanPurchase) {
            return;
        }

        $this->linkResent = RateLimiter::attempt('plan-purchase-link:'.$purchase->id, 3, fn (): bool => (bool) dispatch(new SendPlanPurchaseClaimLink($purchase->id)), 600) !== false;
    }

    private function hasPendingGoogleClaim(): bool
    {
        return $this->purchaseId !== null
            && $this->purchaseId === session('snap_to_track.purchase')
            && $this->purchaseId === session('snap_to_track.google_claim.purchase');
    }
};
?>

@php
    $purchase = $this->purchase;
    $state = $this->state;
    $gutter = 'pr-[max(1rem,env(safe-area-inset-right))] pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1.5rem,env(safe-area-inset-right))] sm:pl-[max(1.5rem,env(safe-area-inset-left))]';
    $press = 'touch-manipulation select-none [-webkit-touch-callout:none] transition duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100';
    $ring = 'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';
    $starterLabel = $purchase instanceof PlanPurchase
        ? strtr(__('common.photo_plans.offer.get_plan'), ['{{name}}' => $purchase->product->name, '{{price}}' => $purchase->product->formatted_price])
        : null;
    $mealWaiting = is_string(session('snap_to_track.result.draft'));
    $supportUrl = $purchase instanceof PlanPurchase
        ? App\Utilities\SupportEmailLink::make('Wrong checkout email for '.$purchase->product->name.' ('.$purchase->reference().')', [
            'I paid for '.$purchase->product->name.', but I entered the wrong email at checkout.',
            '',
            'Purchase reference: '.$purchase->reference(),
            'Correct email: ',
        ])
        : null;
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
            @if ($state === 'confirming')
                @if ($confirmUntil > now()->getTimestamp())
                    <div wire:poll.3s="pollPaymentStatus" class="flex flex-col gap-3">
                        <x-snap-result.icon name="loader" class="size-6 text-primary motion-safe:animate-spin" />
                        <h1 class="text-2xl font-semibold tracking-tight">Confirming your payment…</h1>
                        <p class="text-sm leading-relaxed text-muted-foreground">This usually takes a moment. Keep this page open and we will take you to the next step.</p>
                    </div>
                @else
                    <h1 class="text-2xl font-semibold tracking-tight">Still confirming your payment</h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">Stripe usually confirms in a moment. Your activation email arrives as soon as it does, so you can close this page.</p>
                    <x-snap-result.button variant="plain" class="bg-muted" wire:click="checkPaymentStatus" wire:loading.attr="disabled" wire:target="checkPaymentStatus">
                        <x-snap-result.icon name="rotate-ccw" wire:loading.remove wire:target="checkPaymentStatus" />
                        <x-snap-result.icon name="loader" wire:loading wire:target="checkPaymentStatus" class="motion-safe:animate-spin" />
                        Check payment status
                    </x-snap-result.button>
                @endif
            @elseif ($state === 'paid')
                <p class="self-start rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary">Payment received</p>
                <h1 class="text-2xl font-semibold tracking-tight text-balance">Activate {{ $purchase->product->name }}</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Choose the Google account with the email you used at checkout.
                </p>
                @session('snap_to_track.google_error')
                    <p role="alert" class="text-sm text-destructive">{{ $value }}</p>
                @endsession
                <x-snap-result.button variant="plain" class="bg-white shadow-[0_1px_2px_rgb(0_0_0/0.06),0_10px_24px_-14px_rgb(0_0_0/0.3)] ring-1 ring-black/[0.06]" :href="route('snap-to-track.purchase.google')">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                    </svg>
                    Continue with Google
                </x-snap-result.button>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Check your inbox to activate by email. We sent a link to <span class="font-medium wrap-anywhere text-foreground">{{ $purchase->maskedEmail() }}</span> that you can open on any device.
                </p>
                @if ($linkResent)
                    <p role="status" class="text-sm font-medium text-primary">Sent. It can take a minute to arrive.</p>
                @else
                    <x-snap-result.button variant="plain" class="bg-muted" wire:click="resendLink" wire:loading.attr="disabled" wire:target="resendLink">
                        Resend the link
                    </x-snap-result.button>
                @endif
                <p class="text-xs leading-relaxed text-muted-foreground">
                    Can't find it? Check your spam folder. Wrong email? Write to <a href="{{ $supportUrl }}" class="font-medium text-primary underline underline-offset-4">support@acara.app</a> and mention reference {{ $purchase->reference() }}.
                </p>
            @elseif ($state === 'claimed')
                <p class="self-start rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary">{{ $purchase->product->name }} is active</p>
                <h1 class="text-2xl font-semibold tracking-tight">Your plan is ready</h1>
                @if (auth()->check() && auth()->id() === $purchase->user_id)
                    <x-snap-result.button :href="route('snap-to-track.index')">
                        <x-snap-result.icon name="camera" />
                        Open Snap to Track
                    </x-snap-result.button>
                @else
                    <p class="text-sm leading-relaxed text-muted-foreground">Log in to the account you activated it on to keep scanning.</p>
                    <x-snap-result.button :href="route('login')">Log in</x-snap-result.button>
                @endif
            @elseif ($state === 'cancelled' || $state === 'open')
                <h1 class="text-2xl font-semibold tracking-tight text-balance">{{ $state === 'cancelled' ? 'This purchase was cancelled' : 'Your checkout is waiting' }}</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ $state === 'cancelled' ? 'It is no longer active, and nothing more will be charged. You can start again whenever you like.' : 'Nothing has been charged. Pick up where you left off whenever you are ready.' }}
                </p>
                <x-snap-result.button variant="plan" wire:click="continueCheckout" wire:loading.attr="disabled" wire:target="continueCheckout">
                    {{ $starterLabel }}
                </x-snap-result.button>
                @error('checkout')
                    <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                @enderror
                <p class="text-center text-xs leading-relaxed text-muted-foreground">{{ __('common.photo_plans.offer.renewal') }}</p>
                <x-snap-result.button variant="plain" class="bg-muted" :href="$mealWaiting ? route('snap-to-track.result') : route('snap-to-track')">
                    {{ $mealWaiting ? 'Back to your meal' : 'Back to Snap to Track' }}
                </x-snap-result.button>
            @else
                <h1 class="text-2xl font-semibold tracking-tight">Nothing to show here yet</h1>
                <p class="text-sm leading-relaxed text-muted-foreground">We could not find a Starter checkout in this browser. If you already paid, check your inbox for your activation link.</p>
                <x-snap-result.button :href="route('snap-to-track')">
                    <x-snap-result.icon name="camera" />
                    Back to Snap to Track
                </x-snap-result.button>
            @endif
        </section>
    </main>
</div>
