@props(['allowance'])

@php
    $offer = $allowance->canUpgrade ? $allowance->offer : null;
    $freeScansLeft = $allowance->enabled && $allowance->mode === 'trial' && ! $allowance->exhausted ? $allowance->remaining : null;
    $variant = $allowance->variant ?? 'baseline';
@endphp

@if ($offer !== null)
    <section
        aria-labelledby="result-offer-heading"
        x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: 'result', variant: @js($variant) }, @js('result:'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt)); $wire.offerViewed('result')"
        x-intersect:enter="actionsOnScreen.offer = true"
        x-intersect:leave="actionsOnScreen.offer = false"
        class="flex flex-col gap-5 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60 sm:p-6"
    >
        <div class="flex flex-col gap-2">
            @if ($allowance->exhausted)
                <p class="self-start rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">Free scans used up</p>
            @elseif ($freeScansLeft !== null)
                <p class="self-start rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ $freeScansLeft }} free {{ Str::plural('scan', $freeScansLeft) }} left</p>
            @endif
            <h2 id="result-offer-heading" class="text-2xl font-semibold tracking-tight">Track every meal this easily</h2>
            <p class="text-sm text-muted-foreground">
                @if ($allowance->exhausted)
                    This meal is still free to save. To keep scanning, {{ $offer->name }} lets you snap every meal, every day.
                @else
                    You got this breakdown from one photo. {{ $offer->name }} lets you do it for every meal, every day.
                @endif
            </p>
        </div>

        <ul class="flex flex-col gap-3">
            @foreach ([
                'Snap any meal and get calories and macros in seconds',
                'Keep every meal in a food log like this one',
                'Get daily calorie and macro goals made for you',
            ] as $benefit)
                <li class="flex items-start gap-3 text-sm">
                    <span aria-hidden="true" class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <x-snap-result.icon name="check" class="size-3.5" stroke-width="3" />
                    </span>
                    <span>{{ $benefit }}</span>
                </li>
            @endforeach
        </ul>

        <div class="flex flex-col gap-2">
            <x-snap-result.button
                class="whitespace-normal"
                wire:click="upgrade('result')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: 'result', variant: {{ Js::from($variant) }} })"
            >
                Keep tracking with {{ $offer->name }} — {{ $offer->formattedPrice }}/month
            </x-snap-result.button>
            <p class="text-center text-xs text-muted-foreground">Renews monthly. Cancel anytime. Daily and weekly AI limits apply.</p>
        </div>

        <div class="flex justify-center border-t border-border/60 pt-4 text-sm">
            <button
                type="button"
                wire:click="save('register', 'result')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track', placement: 'result' })"
                class="min-h-11 rounded-md px-2 font-medium underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
            >
                Not now — save this meal free
            </button>
        </div>
    </section>
@endif
