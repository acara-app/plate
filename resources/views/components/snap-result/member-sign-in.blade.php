@props(['dark' => false])

@php
    $pill = 'inline-flex min-h-11 flex-1 touch-manipulation items-center justify-center gap-2 rounded-full px-4 text-sm font-semibold transition duration-150 ease-out outline-none select-none active:scale-[0.97] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none motion-reduce:active:scale-100';
    $tone = $dark ? 'bg-white text-black focus-visible:ring-[3px] focus-visible:ring-white/70' : 'bg-muted text-foreground hover:bg-muted/70 focus-visible:ring-[3px] focus-visible:ring-ring/50';
@endphp

<div {{ $attributes->class('flex flex-col items-center gap-2') }}>
    <p @class(['text-sm', 'text-white/65' => $dark, 'text-muted-foreground' => ! $dark])>{{ __('common.photo_plans.offer.member_prompt') }}</p>
    <div class="flex w-full flex-wrap gap-2">
        <button
            type="button"
            wire:click="signInForPlan('google')"
            wire:loading.attr="disabled"
            wire:target="signInForPlan"
            x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { source: 'public_snap_to_track', auth_path: 'google', intent: 'upgrade' })"
            class="{{ $pill }} {{ $tone }}"
        >
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-4 shrink-0">
                <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.81z" />
                <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.88-3c-1.07.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.95H1.28v3.1A12 12 0 0 0 12 24z" />
                <path fill="#FBBC05" d="M5.29 14.29A7.2 7.2 0 0 1 4.91 12c0-.79.14-1.56.38-2.29V6.61H1.28A12 12 0 0 0 0 12c0 1.94.46 3.77 1.28 5.39l4.01-3.1z" />
                <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.61 4.59 1.8l3.44-3.44A11.97 11.97 0 0 0 12 0 12 12 0 0 0 1.28 6.61l4.01 3.1C6.23 6.86 8.88 4.75 12 4.75z" />
            </svg>
            {{ __('common.photo_plans.offer.member_google') }}
        </button>
        <button
            type="button"
            wire:click="signInForPlan('login')"
            wire:loading.attr="disabled"
            wire:target="signInForPlan"
            x-on:click="window.acaraTrack?.('snap_to_track_auth_started', { source: 'public_snap_to_track', auth_path: 'login', intent: 'upgrade' })"
            class="{{ $pill }} {{ $tone }}"
        >
            {{ __('common.photo_plans.offer.member_email') }}
        </button>
    </div>
</div>
