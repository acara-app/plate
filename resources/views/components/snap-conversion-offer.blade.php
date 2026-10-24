@props(['allowance', 'placement', 'hasResult' => false])

<article
    @if ($allowance->canUpgrade && ($allowance->exhausted || $allowance->preview !== null))
        x-data x-intersect.once="window.acaraTrackOnce?.('snap_to_track_offer_viewed', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') }, @js($placement.':'.$allowance->mode.':'.$allowance->used.':'.$allowance->resetsAt))"
    @endif
    class="border border-[#1A1814] bg-[#1A1814] p-6 text-[#F2EBDD] sm:p-8"
>
    @if ($allowance->preview?->status === 'active')
        <p class="text-sm font-semibold">Your three-day photo habit</p>
        <p class="mt-2 text-sm">{{ $allowance->remaining }} scan available in this 24-hour window.</p>
        <p class="mt-2 text-sm text-[#F2EBDD]/85">
            Preview ends <time x-data x-text="new Date(@js($allowance->preview->expiresAt)).toLocaleString()">{{ $allowance->preview->expiresAt }}</time>.
            @if ($allowance->preview->nextScanAt)
                Next scan <time x-data x-text="new Date(@js($allowance->preview->nextScanAt)).toLocaleString()">{{ $allowance->preview->nextScanAt }}</time>.
            @endif
        </p>
    @elseif ($allowance->preview?->status === 'available')
        <h2 class="text-2xl font-bold">Build a three-day tracking habit</h2>
        <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">Try three more photo scans: one per 24 hours for three days. Unused scans don't roll over. No card or automatic subscription.</p>
        <button type="button" wire:click="startPreview" wire:loading.attr="disabled" wire:target="startPreview" class="mt-5 inline-flex min-h-12 w-full items-center justify-center bg-[#F2EBDD] px-4 py-3 font-semibold text-[#1A1814] focus-visible:outline-2 focus-visible:outline-offset-4">
            {{ auth()->user()?->hasVerifiedEmail() ? 'Activate my three-day preview' : 'Create or verify my free account' }}
        </button>
    @elseif ($allowance->exhausted)
        <p class="text-sm font-semibold">Scan limit reached</p>
        <h2 class="mt-3 text-2xl font-bold">{{ $allowance->preview?->status === 'expired' ? 'Your three-day preview is complete.' : 'Your free scans are complete.' }}</h2>
    @elseif ($allowance->enabled && $allowance->mode === 'trial')
        <h2 class="text-xl font-bold">{{ $allowance->remaining }} {{ $allowance->remaining === 1 ? 'free scan remaining' : 'free scans remaining' }}</h2>
        <p class="mt-2 text-sm text-[#F2EBDD]/85">Try another meal and see how photo tracking fits your day.</p>
        @if ($hasResult)
            <button type="button" wire:click="clearPhoto" class="mt-5 inline-flex min-h-12 w-full items-center justify-center bg-[#F2EBDD] px-4 py-3 font-semibold text-[#1A1814] focus-visible:outline-2 focus-visible:outline-offset-4">Use my remaining free scan</button>
        @endif
    @else
        <h2 class="text-xl font-bold">Keep this meal in your food log</h2>
    @endif

    @if ($allowance->canUpgrade && $allowance->offer && ($allowance->exhausted || $allowance->preview !== null))
        <div class="mt-5">
            <button type="button" wire:click="upgrade" wire:loading.attr="disabled" wire:target="upgrade" x-on:click="window.acaraTrack?.('snap_to_track_upgrade_click', { source: 'public_snap_to_track', placement: @js($placement), variant: @js($allowance->variant ?? 'baseline') })" class="inline-flex min-h-12 w-full items-center justify-center bg-[#A04A28] px-4 py-3 text-center font-semibold text-white hover:bg-[#893E20] focus-visible:outline-2 focus-visible:outline-offset-4">
                Continue scanning with {{ $allowance->offer->name }} — {{ $allowance->offer->formattedPrice }}/month
            </button>
            <p class="mt-3 text-sm leading-relaxed text-[#F2EBDD]/85">Daily and weekly AI limits apply. Renews monthly. Cancel anytime. No overage charges.</p>
        </div>
    @endif

    @if ($hasResult)
        <button type="button" wire:click="saveMeal('register')" wire:loading.attr="disabled" wire:target="saveMeal" x-on:click="window.acaraTrack?.('snap_to_track_save_click', { source: 'public_snap_to_track' })" class="mt-5 inline-flex min-h-12 w-full items-center justify-center border border-[#F2EBDD] px-4 py-3 font-semibold focus-visible:outline-2 focus-visible:outline-offset-4">Save this meal free</button>
        <p class="mt-2 text-sm text-[#F2EBDD]/85">Save the breakdown and correct anything the AI got wrong.</p>
    @endif
    @guest
        <button type="button" wire:click="{{ $hasResult ? "saveMeal('login')" : "continueInApp('login')" }}" class="mt-3 min-h-11 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4">Already have an account? Log in</button>
    @endguest
</article>
