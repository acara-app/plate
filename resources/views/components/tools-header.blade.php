@props([
    // 'light' = white background (public landing pages, default)
    // 'cream' = editorial paper (redesigned tool pages)
    'theme' => 'light',
])

@php
    $tone = $theme === 'cream'
        ? [
            'wrapper' => 'border-[#D9CFBC] bg-[#F2EBDD]/85',
            'logo' => 'text-[#1A1814]',
            'login' => 'text-[#3D3833] hover:text-[#1A1814]',
            'cta_outline' => 'border-[#1A1814] bg-transparent text-[#1A1814] hover:bg-[#1A1814] hover:text-[#F2EBDD]',
            'cta_filled' => 'border-[#1A1814] bg-[#1A1814] text-[#F2EBDD] hover:bg-[#3D3833]',
            'show_dot' => true,
        ]
        : [
            'wrapper' => 'border-slate-200 bg-white/90 dark:border-slate-800 dark:bg-slate-900/90',
            'logo' => 'text-slate-900 dark:text-white',
            'login' => 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            'cta_outline' => 'border-slate-900 bg-transparent text-slate-900 hover:bg-slate-900 hover:text-white dark:border-white dark:text-white dark:hover:bg-white dark:hover:text-slate-900',
            'cta_filled' => 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100',
            'show_dot' => false,
        ];
@endphp

<header {{ $attributes->merge(['class' => 'sticky top-0 z-50 border-b backdrop-blur-md '.$tone['wrapper']]) }}>
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a
            href="/"
            class="flex items-center gap-3 transition hover:opacity-80"
            aria-label="Acara Plate home"
        >
            <span class="text-2xl leading-none" role="img" aria-label="strawberry">🍓</span>
            <span class="font-bold text-xl leading-none tracking-[-0.01em] sm:text-2xl {{ $tone['logo'] }}">
                Acara Plate
            </span>
            @if ($tone['show_dot'])
                <span class="hidden h-1.5 w-1.5 rounded-full bg-[#C4623A] sm:inline-block" aria-hidden="true"></span>
            @endif
        </a>

        <div class="flex items-center gap-3 sm:gap-4">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-none border px-4 py-2 font-mono text-[11px] uppercase tracking-[0.16em] transition sm:px-5 {{ $tone['cta_filled'] }}"
                >
                    Dashboard
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="hidden font-mono text-[11px] uppercase tracking-[0.16em] transition sm:inline {{ $tone['login'] }}"
                >
                    Log in
                </a>
                <a
                    href="{{ route('register') }}"
                    class="inline-flex items-center gap-2 rounded-none border px-4 py-2 font-mono text-[11px] uppercase tracking-[0.16em] transition sm:px-5 {{ $tone['cta_outline'] }}"
                >
                    Get started
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            @endauth
        </div>
    </div>
</header>
