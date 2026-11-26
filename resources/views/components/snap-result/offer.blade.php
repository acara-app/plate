@props(['allowance', 'offer' => null])

@php
    $variant = $allowance->variant ?? 'baseline';
@endphp

@if ($offer !== null)
    <section
        aria-labelledby="result-offer-heading"
        x-data
        x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: 'result', variant: @js($variant) }, @js('result:'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt)); $wire.offerViewed('result')"
        class="flex flex-col gap-5 rounded-[1.75rem] bg-card p-5 sm:p-6"
    >
        <div class="flex flex-col gap-2">
            @if ($allowance->exhausted)
                <p class="self-start rounded-full bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground">{{ __('common.photo_plans.free.used') }}</p>
            @endif
            <h2 id="result-offer-heading" class="text-2xl font-semibold tracking-tight text-balance">{{ __('common.photo_plans.offer.heading') }}</h2>
            <p class="text-sm leading-relaxed text-muted-foreground">
                {{ __('common.photo_plans.offer.save_note') }}
                {!! e(strtr(__('common.photo_plans.offer.body'), ['@{{name}}' => $offer->name])) !!}
            </p>
        </div>

        <ul class="flex flex-col gap-3">
            @foreach (__('common.photo_plans.offer.benefits') as $benefit)
                <li class="flex items-start gap-3 text-sm">
                    <span aria-hidden="true" class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <x-snap-result.icon name="check" class="size-3.5" stroke-width="3" />
                    </span>
                    <span>{{ $benefit }}</span>
                </li>
            @endforeach
        </ul>

        <div
            x-intersect:enter="actionsOnScreen.offer = true"
            x-intersect:leave="actionsOnScreen.offer = false"
            class="flex flex-col gap-2"
        >
            <x-snap-result.button
                variant="plan"
                wire:click="upgrade('result')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade,signInForPlan"
                x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: 'result', variant: {{ Js::from($variant) }} })"
            >
                {!! e(strtr(__('common.photo_plans.offer.get_plan'), ['@{{name}}' => $offer->name, '@{{price}}' => $offer->formattedPrice])) !!}
            </x-snap-result.button>
            @error('checkout')
                <p role="alert" class="text-center text-sm text-destructive">{{ $message }}</p>
            @enderror
            <div class="flex flex-col gap-1 text-center leading-relaxed">
                @guest
                    <p class="text-sm font-medium">{{ __('common.photo_plans.offer.no_account') }}</p>
                @endguest
                <p class="text-xs text-muted-foreground">{{ __('common.photo_plans.offer.renewal') }}</p>
            </div>
            @guest
                <button
                    type="button"
                    wire:click="signInForPlan"
                    wire:loading.attr="disabled"
                    wire:target="save,upgrade,signInForPlan"
                    x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { source: 'public_snap_to_track', auth_path: 'login', intent: 'upgrade' })"
                    class="inline-flex min-h-11 touch-manipulation items-center justify-center self-center rounded-full px-3 text-sm font-medium text-primary transition duration-150 ease-out outline-none select-none [-webkit-touch-callout:none] focus-visible:ring-[3px] focus-visible:ring-ring/50 active:scale-[0.97] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none motion-reduce:active:scale-100"
                >
                    {{ __('common.photo_plans.offer.login') }}
                </button>
            @endguest
            <button
                type="button"
                wire:click="save('register')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade,signInForPlan"
                x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'result' })"
                class="inline-flex min-h-11 touch-manipulation items-center justify-center self-center rounded-full px-3 text-sm font-medium transition duration-150 ease-out outline-none select-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 active:scale-[0.97] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none motion-reduce:active:scale-100"
            >
                {{ __('common.photo_plans.offer.save_free') }}
            </button>
        </div>
    </section>
@endif
