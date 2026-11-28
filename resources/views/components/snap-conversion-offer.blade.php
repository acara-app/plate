@props(['allowance', 'placement'])

@php
    $properties = $allowance->publicOfferProperties($placement);
    $pill = 'inline-flex min-h-14 w-full touch-manipulation items-center justify-center rounded-full px-6 text-center text-base font-semibold select-none transition duration-150 ease-out outline-none focus-visible:ring-[3px] focus-visible:ring-white/70 active:scale-[0.97] disabled:opacity-60 motion-reduce:transition-none';
@endphp

<article
    @if ($allowance->canUpgrade && $allowance->offer && $allowance->exhausted)
        x-data x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', @js($properties), @js($placement.':'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt)); $wire.offerViewed()"
    @endif
    class="rounded-[2rem] bg-white/10 p-6 text-white"
>
    @if ($allowance->exhausted)
        <p class="text-sm font-medium text-primary">{{ __('common.photo_plans.offer.limit_heading') }}</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight">{{ __('common.photo_plans.free.used') }}</h2>
    @elseif ($allowance->enabled && $allowance->mode === 'trial')
        <h2 class="text-xl font-bold tracking-tight">{!! e(strtr(__('common.photo_plans.free.remaining_'.($allowance->remaining === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $allowance->remaining])) !!}</h2>
        <p class="mt-2 text-[15px] text-white/75">{{ __('common.photo_plans.offer.next_meal') }}</p>
    @else
        <h2 class="text-xl font-bold tracking-tight">{{ __('common.photo_plans.offer.keep_meal') }}</h2>
    @endif

    @if ($allowance->canUpgrade && $allowance->offer && $allowance->exhausted)
        <p class="mt-3 text-[15px] leading-relaxed text-white/80">{!! e(strtr(__('common.photo_plans.offer.body'), ['@{{name}}' => $allowance->offer->name])) !!}</p>
        <div class="mt-5">
            <button type="button" wire:click="upgrade" wire:loading.attr="disabled" wire:target="upgrade,signInForPlan" x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', @js($properties))" class="{{ $pill }} bg-primary py-3 text-emerald-950">
                {!! e(strtr(__('common.photo_plans.offer.get_plan'), ['@{{name}}' => $allowance->offer->name, '@{{price}}' => $allowance->offer->formattedPrice])) !!}
            </button>
            @error('checkout')
                <p role="alert" class="mt-3 text-sm text-rose-300">{{ $message }}</p>
            @enderror
            @guest
                <p class="mt-3 text-sm font-medium text-white">{{ __('common.photo_plans.offer.no_account') }}</p>
            @endguest
            <p class="mt-1 text-sm leading-relaxed text-white/65">{{ __('common.photo_plans.offer.renewal') }}</p>
        </div>
    @endif

    @guest
        @if ($allowance->canUpgrade && $allowance->offer && $allowance->exhausted)
            <button type="button" wire:click="signInForPlan" wire:loading.attr="disabled" wire:target="upgrade,signInForPlan" class="mt-3 min-h-11 touch-manipulation rounded-full text-sm font-medium underline underline-offset-4 outline-none select-none focus-visible:ring-[3px] focus-visible:ring-white/70">{{ __('common.photo_plans.offer.login') }}</button>
        @else
            <button type="button" wire:click="continueInApp('login')" class="mt-2 min-h-11 touch-manipulation rounded-full text-sm font-medium underline underline-offset-4 outline-none focus-visible:ring-[3px] focus-visible:ring-white/70">{{ __('common.photo_plans.offer.login') }}</button>
        @endif
    @endguest
</article>
