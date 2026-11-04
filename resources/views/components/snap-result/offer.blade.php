@props(['allowance'])

@php
    $offer = $allowance->canUpgrade ? $allowance->offer : null;
    $freeScansLeft = $allowance->enabled && $allowance->mode === 'trial' && ! $allowance->exhausted ? $allowance->remaining : null;
    $variant = $allowance->variant ?? 'baseline';
@endphp

@if ($offer === null)
    <section aria-labelledby="result-offer-heading" class="flex flex-col gap-4 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-primary/30 sm:p-6">
        <h2 id="result-offer-heading" class="text-xl font-semibold tracking-tight">Keep this meal in your food log</h2>

        <div class="flex flex-col gap-2">
            <x-snap-result.button
                wire:click="save('register')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track' })"
            >
                Save this meal free
            </x-snap-result.button>
            <p class="text-sm text-muted-foreground">Save the breakdown and correct anything the AI got wrong.</p>
        </div>

        <button
            type="button"
            wire:click="save('login')"
            wire:loading.attr="disabled"
            wire:target="save,upgrade"
            class="min-h-11 self-start rounded-md text-sm font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
        >
            Already have an account? Log in
        </button>
    </section>
@else
    <section
        aria-labelledby="result-offer-heading"
        x-data
        x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: 'result', variant: @js($variant) }, @js('result:'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt))"
        class="flex flex-col gap-5 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-primary/30 sm:p-6"
    >
        <div class="flex flex-col gap-2">
            @if ($allowance->exhausted)
                <p class="self-start rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">Free scans used up</p>
            @elseif ($freeScansLeft !== null)
                <p class="self-start rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ $freeScansLeft }} free {{ Str::plural('scan', $freeScansLeft) }} left</p>
            @endif
            <h2 id="result-offer-heading" class="text-2xl font-semibold tracking-tight">Track every meal this easily</h2>
            <p class="text-sm text-muted-foreground">You got this breakdown from one photo. {{ $offer->name }} lets you do it for every meal, every day.</p>
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
                wire:click="upgrade"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: 'result', variant: {{ Js::from($variant) }} })"
            >
                Keep tracking with {{ $offer->name }} — {{ $offer->formattedPrice }}/month
            </x-snap-result.button>
            <p class="text-center text-xs text-muted-foreground">Renews monthly. Cancel anytime. Daily and weekly AI limits apply.</p>
        </div>

        <div class="flex flex-col items-center gap-1 border-t border-border/60 pt-4 text-sm">
            <button
                type="button"
                wire:click="save('register')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track' })"
                class="min-h-11 rounded-md px-2 font-medium underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
            >
                Not now — save this meal free
            </button>
            <button
                type="button"
                wire:click="save('login')"
                wire:loading.attr="disabled"
                wire:target="save,upgrade"
                class="min-h-11 rounded-md px-2 font-medium text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
            >
                Already have an account? Log in
            </button>
        </div>
    </section>
@endif
