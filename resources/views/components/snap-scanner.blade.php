@php
    $roundControl = 'flex size-14 shrink-0 touch-manipulation items-center justify-center rounded-full outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-white/50';
@endphp

<div
    wire:ignore
    x-data="snapScanner"
    x-on:snap-scanner-open.window="show()"
    x-on:keydown.escape.window="open && close()"
>
    <div
        x-show="open"
        x-trap.inert.noscroll="open"
        role="dialog"
        aria-modal="true"
        aria-labelledby="snap-scanner-title"
        aria-describedby="snap-scanner-tip"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80"
        style="display: none;"
    >
        <div class="grid h-dvh w-full grid-rows-[auto_auto_minmax(0,1fr)_auto] overflow-hidden bg-black text-white lg:h-[48rem] lg:max-h-[90dvh] lg:max-w-lg lg:rounded-3xl [@media(orientation:landscape)_and_(max-height:32rem)]:grid-cols-[minmax(0,1fr)_auto] [@media(orientation:landscape)_and_(max-height:32rem)]:grid-rows-[auto_minmax(0,1fr)]">
            <h2 id="snap-scanner-title" class="sr-only">{{ __('common.snap_to_track.camera.title') }}</h2>

            <div class="row-start-1 flex items-center justify-between gap-4 pt-[max(1.25rem,env(safe-area-inset-top))] pr-[max(1.25rem,env(safe-area-inset-right))] pb-4 pl-[max(1.25rem,env(safe-area-inset-left))] [@media(max-height:32rem)]:pt-[max(0.75rem,env(safe-area-inset-top))] [@media(max-height:32rem)]:pb-3 [@media(orientation:landscape)_and_(max-height:32rem)]:col-span-full">
                <button
                    type="button"
                    x-on:click="close()"
                    aria-label="{{ __('common.snap_to_track.camera.close') }}"
                    class="{{ $roundControl }} bg-white/10 text-white hover:bg-white/20"
                >
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
                <button
                    type="button"
                    x-show="torchAvailable"
                    x-on:click="toggleTorch()"
                    x-bind:aria-label="torchOn ? @js(__('common.snap_to_track.camera.flash_off')) : @js(__('common.snap_to_track.camera.flash_on'))"
                    x-bind:class="torchOn ? 'bg-white text-black hover:bg-white/90' : 'bg-white/10 text-white hover:bg-white/20'"
                    class="{{ $roundControl }}"
                    style="display: none;"
                >
                    <svg x-show="torchOn" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z" />
                    </svg>
                    <svg x-show="! torchOn" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10.768 5.111 13.44 2.44a1.5 1.5 0 012.474 1.561l-1.633 4.625" />
                        <path d="m18.889 13.232.672-.672A1.5 1.5 0 0018.5 10h-2.844" />
                        <path d="m2 2 20 20" />
                        <path d="m7.94 7.94-3.5 3.499A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l5.5-5.5" />
                    </svg>
                </button>
            </div>

            <p id="snap-scanner-tip" class="row-start-2 mr-[max(1rem,env(safe-area-inset-right))] mb-4 ml-[max(1rem,env(safe-area-inset-left))] flex items-start gap-3 rounded-2xl bg-white/10 px-4 py-3 text-sm leading-snug text-white/90 [@media(max-height:32rem)]:hidden">
                <svg class="size-5 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
                    <path d="M9 18h6" />
                    <path d="M10 22h4" />
                </svg>
                <span>{{ __('common.snap_to_track.camera.tip') }}</span>
            </p>

            <div class="relative row-start-3 mr-[max(1rem,env(safe-area-inset-right))] ml-[max(1rem,env(safe-area-inset-left))] flex min-h-0 min-w-0 items-center justify-center @container-size [@media(orientation:landscape)_and_(max-height:32rem)]:col-start-1 [@media(orientation:landscape)_and_(max-height:32rem)]:row-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:mb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <div class="relative aspect-square w-[min(100cqw,100cqh)] shrink-0 overflow-hidden rounded-3xl bg-neutral-900">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        muted
                        aria-label="{{ __('common.snap_to_track.camera.live_view') }}"
                        x-on:playing="playing()"
                        class="absolute inset-0 size-full object-cover object-center"
                    ></video>
                    <div x-show="status === 'ready'" aria-hidden="true" class="pointer-events-none absolute inset-3" style="display: none;">
                        <span class="absolute top-0 left-0 size-10 rounded-tl-2xl border-t-4 border-l-4 border-emerald-500"></span>
                        <span class="absolute top-0 right-0 size-10 rounded-tr-2xl border-t-4 border-r-4 border-emerald-500"></span>
                        <span class="absolute bottom-0 left-0 size-10 rounded-bl-2xl border-b-4 border-l-4 border-emerald-500"></span>
                        <span class="absolute right-0 bottom-0 size-10 rounded-br-2xl border-r-4 border-b-4 border-emerald-500"></span>
                    </div>
                </div>

                <div x-show="status !== 'ready'" class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-y-auto p-4">
                    <div x-show="error" role="alert" class="pointer-events-auto w-full max-w-sm rounded-2xl border border-white/15 bg-neutral-900 p-4 text-sm" style="display: none;">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14.564 14.558a3 3 0 1 1-4.122-4.121" />
                                <path d="m2 2 20 20" />
                                <path d="M20 20H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 .819-.175" />
                                <path d="M9.695 4.024A2 2 0 0 1 10.004 4h3.993a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v7.344" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ __('common.snap_to_track.camera.error_heading') }}</p>
                                @foreach (['permission_denied', 'unavailable', 'unsupported', 'capture_failed'] as $reason)
                                    <p x-show="error === @js($reason)" class="mt-1 leading-relaxed text-white/70" style="display: none;">{{ __('common.snap_to_track.camera.'.$reason) }}</p>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-4 grid gap-2">
                            <button
                                type="button"
                                x-on:click="$refs.phoneCamera.click()"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 font-semibold text-black transition hover:bg-white/90 focus-visible:ring-[3px] focus-visible:ring-white/50 focus-visible:outline-none"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z" />
                                    <circle cx="12" cy="13" r="3" />
                                </svg>
                                {{ __('common.snap_to_track.camera.use_phone_camera') }}
                            </button>
                            <button
                                type="button"
                                x-on:click="start()"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 px-4 py-2 font-semibold text-white transition hover:bg-white/10 focus-visible:ring-[3px] focus-visible:ring-white/50 focus-visible:outline-none"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                    <path d="M3 3v5h5" />
                                </svg>
                                {{ __('common.snap_to_track.camera.retry') }}
                            </button>
                        </div>
                    </div>
                    <div x-show="! error" role="status" class="flex flex-col items-center gap-4 text-center">
                        <svg class="size-7 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                        </svg>
                        <p x-text="status === 'paused' ? @js(__('common.snap_to_track.camera.paused')) : @js(__('common.snap_to_track.camera.loading'))"></p>
                    </div>
                </div>
            </div>

            <div class="row-start-4 flex min-h-0 flex-col justify-center pt-6 pr-[max(1.5rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))] pl-[max(1.5rem,env(safe-area-inset-left))] [@media(max-height:32rem)]:pt-4 [@media(max-height:32rem)]:pb-[max(1rem,env(safe-area-inset-bottom))] [@media(orientation:landscape)_and_(max-height:32rem)]:col-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:row-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:pt-0 [@media(orientation:landscape)_and_(max-height:32rem)]:pl-0">
                <div class="grid grid-cols-3 items-center justify-items-center gap-4 [@media(orientation:landscape)_and_(max-height:32rem)]:flex [@media(orientation:landscape)_and_(max-height:32rem)]:flex-col">
                    <button
                        type="button"
                        x-on:click="$refs.library.click()"
                        aria-label="{{ __('common.snap_to_track.index.choose_photo') }}"
                        class="{{ $roundControl }} bg-white/10 text-white hover:bg-white/20"
                    >
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 5h6" />
                            <path d="M19 2v6" />
                            <path d="M21 11.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7.5" />
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                            <circle cx="9" cy="9" r="2" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        x-on:click="capture()"
                        x-bind:disabled="status !== 'ready' || capturing"
                        aria-label="{{ __('common.snap_to_track.camera.capture') }}"
                        class="flex size-20 shrink-0 touch-manipulation items-center justify-center rounded-full border-4 border-white p-1 outline-none transition-transform focus-visible:ring-[3px] focus-visible:ring-white/50 active:scale-95 disabled:opacity-50 motion-reduce:transition-none"
                    >
                        <span class="size-full rounded-full bg-emerald-500"></span>
                    </button>
                    <span class="size-14 shrink-0 [@media(orientation:landscape)_and_(max-height:32rem)]:hidden" aria-hidden="true"></span>
                </div>
                <input type="file" accept="image/*" class="hidden" tabindex="-1" x-ref="library" x-on:change="pick($event.target)">
                <input type="file" accept="image/*" capture="environment" class="hidden" tabindex="-1" x-ref="phoneCamera" x-on:change="pick($event.target)">
            </div>
        </div>
    </div>
</div>
