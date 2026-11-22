@section('title', 'Install Acara Plate on Your Phone | iOS App + PWA')
@section('meta_description', 'Two ways to put Plate on your iPhone: Acara Health Sync on the App Store for automatic Apple Health syncing, or our Progressive Web App (PWA) that installs straight from Safari or Chrome.')

@section('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How do I install Acara Plate on my phone?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "There are two options. (1) iPhone users who want Apple Health data to sync automatically can install Acara Health Sync from the App Store at {{ config('plate.health_sync.app_store_url') }}. (2) Anyone who just wants the Plate dashboard on their home screen can install our Progressive Web App: on iPhone, open Safari and tap Share then 'Add to Home Screen'; on Android, open Chrome and tap the menu then 'Install app'."
            }
        },
        {
            "@@type": "Question",
            "name": "What is a Progressive Web App (PWA)?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A PWA is a web application that can be installed on your device and accessed from your home screen, just like a native app. It offers faster load times, a more immersive full-screen experience, and works with your device's native browser for the best performance."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to download anything from the App Store?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Only if you want Apple Health data to sync automatically. Acara Health Sync is our iOS companion app on the App Store ({{ config('plate.health_sync.app_store_url') }}) that reads Apple Health data and sends it to your Plate dashboard. If you just want the Plate dashboard on your home screen, our Progressive Web App installs directly from your browser — no App Store download required."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Install Acara Plate PWA",
    "description": "Learn how to install Acara Plate as a Progressive Web App on your iPhone or Android device for faster access and a native app experience.",
    "url": "{{ url('/install-app') }}",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    }
}
</script>
@endsection

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center text-slate-600 dark:text-slate-400 hover:underline z-50 relative"
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl speakable-intro">
                <h1>Install Acara Plate</h1>
                <p>
                    There are two ways to put Plate on your iPhone, and they do different jobs. Pick whichever fits what you need — or grab both, most people do.
                </p>
            </div>

            {{-- Two-way comparison: native iOS vs PWA --}}
            <div class="mx-auto mt-10 grid max-w-4xl gap-6 not-prose md:grid-cols-2">
                {{-- Native iOS app --}}
                <div class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-linear-to-br from-emerald-50 via-white to-white p-6 shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/30 dark:via-slate-950 dark:to-slate-950">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        </span>
                        Native iOS — Now available
                    </span>
                    <h2 class="mt-3 text-xl font-bold text-slate-900 dark:text-white">Acara Health Sync</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        If you want Apple Health data — glucose, weight, sleep, activity — to flow into Plate automatically, this is what you want. The iPhone-only companion reads HealthKit, encrypts everything on your device, and ships it straight to your dashboard.
                    </p>
                    <ul class="mt-4 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Automatic Apple Health sync
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            End-to-end AES-256-GCM encryption
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Free &amp; open source
                        </li>
                    </ul>
                    <div class="mt-5 flex flex-col items-start gap-2">
                        <x-app-store-badge size="md" />
                        <p class="text-[11px] text-slate-500 dark:text-slate-500">Requires iPhone on iOS {{ config('plate.health_sync.minimum_ios_version') }} or later</p>
                    </div>
                </div>

                {{-- Progressive Web App --}}
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Any device — Browser install
                    </span>
                    <h2 class="mt-3 text-xl font-bold text-slate-900 dark:text-white">Progressive Web App</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        Want the Plate dashboard on your home screen without an App Store download? The PWA installs straight from Safari or Chrome in under 30 seconds. Works on iPhone, iPad, Android, and desktop.
                    </p>
                    <ul class="mt-4 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            No App Store required
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Works on any device
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Full-screen, home-screen icon
                        </li>
                    </ul>
                    <div class="mt-5">
                        <a href="#pwa-instructions" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-all hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            See install steps
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div id="pwa-instructions" class="prose prose-slate dark:prose-invert mx-auto max-w-4xl mt-12 scroll-mt-24">
                <h2>Install the PWA on iOS (Safari)</h2>
                <ol>
                    <li>Open <strong>Safari</strong> on your iPhone or iPad.</li>
                    <li>Navigate to <a href="{{ url('/') }}">{{ url('/') }}</a>.</li>
                    <li>Tap the <strong>Share</strong> button (the square with an arrow pointing up) at the bottom of the screen.</li>
                    <li>Scroll down and tap <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong> in the top right corner.</li>
                </ol>

                <h2>Android (Chrome)</h2>
                <ol>
                    <li>Open <strong>Chrome</strong> on your Android device.</li>
                    <li>Navigate to <a href="{{ url('/') }}">{{ url('/') }}</a>.</li>
                    <li>Tap the <strong>Menu</strong> icon (three dots) in the top right corner.</li>
                    <li>Tap <strong>Install app</strong> or <strong>Add to Home screen</strong>.</li>
                    <li>Follow the on-screen instructions to complete the installation.</li>
                </ol>

                <p>
                    Not ready to install anything? The <x-snap-link location="install_app">free photo calorie counter</x-snap-link> works right in your browser.
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
