@props(['allowance', 'placement'])

@php
    $pill = 'inline-flex min-h-14 w-full touch-manipulation items-center justify-center rounded-full px-6 text-center text-base font-semibold select-none transition duration-150 ease-out outline-none focus-visible:ring-[3px] focus-visible:ring-white/70 active:scale-[0.97] disabled:opacity-60 motion-reduce:transition-none';
@endphp

<article
    @if ($allowance->canUpgrade && ($allowance->exhausted || $allowance->preview !== null))
        x-data x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') }, @js($placement.':'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt)); $wire.offerViewed(@js($placement))"
    @endif
    class="rounded-[2rem] bg-white/10 p-6 text-white"
>
    @if ($allowance->preview?->status === 'active')
        <p class="text-[15px] font-semibold">{{ __('common.photo_plans.preview.heading') }}</p>
        <p class="mt-2 text-[15px] text-white/80">{!! e(strtr(__('common.photo_plans.preview.remaining'), ['@{{count}}' => (string) $allowance->remaining])) !!}</p>
        <p class="mt-2 text-sm leading-relaxed text-white/65">
            <span x-data x-text="@js(__('common.photo_plans.preview.ends')).replace('@{{time}}', new Date(@js($allowance->preview->expiresAt)).toLocaleString())"></span>
            @if ($allowance->preview->nextScanAt)
                <span x-data x-text="@js(__('common.photo_plans.preview.next')).replace('@{{time}}', new Date(@js($allowance->preview->nextScanAt)).toLocaleString())"></span>
            @endif
        </p>
    @elseif ($allowance->preview?->status === 'available')
        <h2 class="text-2xl font-bold tracking-tight">{{ __('common.photo_plans.preview.available') }}</h2>
        <p class="mt-2 text-[15px] leading-relaxed text-white/75">{{ __('common.photo_plans.preview.body') }}</p>
        <button type="button" wire:click="startPreview" wire:loading.attr="disabled" wire:target="startPreview" class="{{ $pill }} mt-5 bg-white text-black">
            {{ auth()->user()?->hasVerifiedEmail() ? __('common.photo_plans.preview.activate') : __('common.photo_plans.preview.verify') }}
        </button>
    @elseif ($allowance->exhausted)
        <p class="text-sm font-medium text-primary">{{ __('common.photo_plans.offer.limit_heading') }}</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight">{{ $allowance->preview?->status === 'expired' ? __('common.photo_plans.preview.complete') : __('common.photo_plans.free.used') }}</h2>
    @elseif ($allowance->enabled && $allowance->mode === 'trial')
        <h2 class="text-xl font-bold tracking-tight">{!! e(strtr(__('common.photo_plans.free.remaining_'.($allowance->remaining === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $allowance->remaining])) !!}</h2>
        <p class="mt-2 text-[15px] text-white/75">{{ __('common.photo_plans.offer.next_meal') }}</p>
    @else
        <h2 class="text-xl font-bold tracking-tight">{{ __('common.photo_plans.offer.keep_meal') }}</h2>
    @endif

    @if ($allowance->canUpgrade && $allowance->offer && ($allowance->exhausted || $allowance->preview !== null))
        <p class="mt-3 text-[15px] leading-relaxed text-white/80">{!! e(strtr(__('common.photo_plans.offer.body'), ['@{{name}}' => $allowance->offer->name])) !!}</p>
        <div class="mt-5">
            <button type="button" wire:click="upgrade(@js($placement))" wire:loading.attr="disabled" wire:target="upgrade" x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') })" class="{{ $pill }} bg-primary py-3 text-emerald-950">
                {!! e(strtr(__('common.photo_plans.offer.cta'), ['@{{name}}' => $allowance->offer->name, '@{{price}}' => $allowance->offer->formattedPrice])) !!}
            </button>
            <p class="mt-3 text-sm leading-relaxed text-white/65">{{ __('common.photo_plans.offer.terms') }}</p>
        </div>
    @endif

    @guest
        <button type="button" wire:click="continueInApp('login')" class="mt-2 min-h-11 touch-manipulation rounded-full text-sm font-medium underline underline-offset-4 outline-none focus-visible:ring-[3px] focus-visible:ring-white/70">{{ __('common.photo_plans.offer.login') }}</button>
    @endguest
</article>
