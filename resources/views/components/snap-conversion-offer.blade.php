@props(['allowance', 'placement'])

<article
    @if ($allowance->canUpgrade && ($allowance->exhausted || $allowance->preview !== null))
        x-data x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') }, @js($placement.':'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt)); $wire.offerViewed(@js($placement))"
    @endif
    class="border border-[#1A1814] bg-[#1A1814] p-6 text-[#F2EBDD] sm:p-8"
>
    @if ($allowance->preview?->status === 'active')
        <p class="text-sm font-semibold">{{ __('common.photo_plans.preview.heading') }}</p>
        <p class="mt-2 text-sm">{!! e(strtr(__('common.photo_plans.preview.remaining'), ['@{{count}}' => (string) $allowance->remaining])) !!}</p>
        <p class="mt-2 text-sm text-[#F2EBDD]/85">
            <span x-data x-text="@js(__('common.photo_plans.preview.ends')).replace('@{{time}}', new Date(@js($allowance->preview->expiresAt)).toLocaleString())"></span>
            @if ($allowance->preview->nextScanAt)
                <span x-data x-text="@js(__('common.photo_plans.preview.next')).replace('@{{time}}', new Date(@js($allowance->preview->nextScanAt)).toLocaleString())"></span>
            @endif
        </p>
    @elseif ($allowance->preview?->status === 'available')
        <h2 class="text-2xl font-bold">{{ __('common.photo_plans.preview.available') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">{{ __('common.photo_plans.preview.body') }}</p>
        <button type="button" wire:click="startPreview" wire:loading.attr="disabled" wire:target="startPreview" class="mt-5 inline-flex min-h-12 w-full items-center justify-center bg-[#F2EBDD] px-4 py-3 font-semibold text-[#1A1814] focus-visible:outline-2 focus-visible:outline-offset-4">
            {{ auth()->user()?->hasVerifiedEmail() ? __('common.photo_plans.preview.activate') : __('common.photo_plans.preview.verify') }}
        </button>
    @elseif ($allowance->exhausted)
        <p class="text-sm font-semibold">{{ __('common.photo_plans.offer.limit_heading') }}</p>
        <h2 class="mt-3 text-2xl font-bold">{{ $allowance->preview?->status === 'expired' ? __('common.photo_plans.preview.complete') : __('common.photo_plans.free.used') }}</h2>
    @elseif ($allowance->enabled && $allowance->mode === 'trial')
        <h2 class="text-xl font-bold">{!! e(strtr(__('common.photo_plans.free.remaining_'.($allowance->remaining === 1 ? 'one' : 'other')), ['@{{count}}' => (string) $allowance->remaining])) !!}</h2>
        <p class="mt-2 text-sm text-[#F2EBDD]/85">{{ __('common.photo_plans.offer.next_meal') }}</p>
    @else
        <h2 class="text-xl font-bold">{{ __('common.photo_plans.offer.keep_meal') }}</h2>
    @endif

    @if ($allowance->canUpgrade && $allowance->offer && ($allowance->exhausted || $allowance->preview !== null))
        <p class="mt-3 text-sm">{!! e(strtr(__('common.photo_plans.offer.body'), ['@{{name}}' => $allowance->offer->name])) !!}</p>
        <div class="mt-5">
            <button type="button" wire:click="upgrade(@js($placement))" wire:loading.attr="disabled" wire:target="upgrade" x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') })" class="inline-flex min-h-12 w-full items-center justify-center bg-[#A04A28] px-4 py-3 text-center font-semibold text-white hover:bg-[#893E20] focus-visible:outline-2 focus-visible:outline-offset-4">
                {!! e(strtr(__('common.photo_plans.offer.cta'), ['@{{name}}' => $allowance->offer->name, '@{{price}}' => $allowance->offer->formattedPrice])) !!}
            </button>
            <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">{{ __('common.photo_plans.offer.terms') }}</p>
        </div>
    @endif

    @guest
        <button type="button" wire:click="continueInApp('login')" class="mt-3 min-h-11 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4">{{ __('common.photo_plans.offer.login') }}</button>
    @endguest
</article>
