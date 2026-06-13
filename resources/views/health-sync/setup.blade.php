@section('title', 'Setup Guide | Acara Health Sync — Connect in 5 Minutes')
@section('meta_description', 'Step-by-step guide to set up Acara Health Sync. Install the iOS app, sign in, and start syncing your Apple Health data to Acara Plate.')
@section('meta_keywords', 'health sync setup, acara plate setup guide, apple health sync, ios health app setup')

@section('head')
    <x-json-ld.health-sync-setup />
@endsection

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ route('health-sync') }}"
            class="-mt-10 mb-12 flex items-center text-slate-600 hover:underline dark:text-slate-400"
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back to Health Sync</span>
        </a>

        <div class="mx-auto max-w-3xl">
            {{-- Header --}}
            <header class="mb-12 speakable-intro">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">Set Up Health Sync</h1>
                <p class="mt-3 text-lg text-slate-600 dark:text-slate-400">
                    Five steps, five minutes, and your Apple Health data flows into Plate automatically.
                </p>
            </header>

            {{-- Requirements --}}
            <section class="mb-12">
                <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">Before You Start</h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-slate-700 dark:text-slate-300"><strong class="text-slate-900 dark:text-white">iPhone running iOS 18.0 or later</strong></p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-slate-700 dark:text-slate-300">
                            <strong class="text-slate-900 dark:text-white">An Acara Plate account</strong> —
                            <a href="{{ route('register') }}" class="text-emerald-600 hover:underline dark:text-emerald-400">create one for free</a> if you haven't yet
                        </p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-slate-700 dark:text-slate-300"><strong class="text-slate-900 dark:text-white">Apple Health with some data</strong> — Apple Watch helps, but isn't required</p>
                    </div>
                </div>
            </section>

            {{-- Steps --}}
            <section class="mb-12 space-y-6">
                {{-- Step 1 --}}
                <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">1</div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Get the App</h3>
                    <div class="mt-3 space-y-3 text-sm text-slate-600 dark:text-slate-400">
                        <p>Install <strong class="text-slate-800 dark:text-slate-200">Acara Health Sync</strong> on your iPhone. It's free, and you only need it on one device.</p>
                        <div>
                            <x-app-store-badge size="md" />
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-500">Requires iOS {{ config('plate.health_sync.minimum_ios_version') }} or later.</p>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">2</div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Sign In</h3>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p>Open <strong class="text-slate-800 dark:text-slate-200">Acara Health Sync</strong> and sign in with the same Acara Plate account you use on the web.</p>
                        <p>The app creates its secure sync credentials after you sign in. They are stored in your iOS Keychain.</p>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">3</div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Pick Your Data</h3>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p>Choose which health categories to share. Toggle individual types on or off — glucose but not reproductive health, exercise but not hearing. Your call.</p>
                        <p>Tap <strong class="text-slate-800 dark:text-slate-200">"Continue"</strong> and approve the Apple Health permissions prompt.</p>
                        <p>Only the types you selected get read. Everything else stays private.</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">4</div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">You're Syncing</h3>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p>That's it. Your dashboard shows the connection status:</p>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs dark:border-slate-600 dark:bg-slate-800">
                            <p class="text-emerald-600 dark:text-emerald-400">&#10003; HealthKit connected</p>
                            <p class="text-emerald-600 dark:text-emerald-400">&#10003; Acara Plate connected</p>
                            <p class="text-slate-500 dark:text-slate-400">&#128336; Last synced: just now</p>
                        </div>
                        <p>Data syncs automatically when you open the app. Want it now? Tap <strong class="text-slate-800 dark:text-slate-200">"Sync Now"</strong> or pull to refresh on the Health Data screen.</p>
                    </div>
                </div>

                {{-- Step 5 --}}
                <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">5</div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Manage Your Connection</h3>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p>Use the app settings to change data permissions, run a manual sync, view sync history, or disconnect the account from the device.</p>
                    </div>
                </div>
            </section>

            {{-- Managing Your Connection --}}
            <section class="mb-12">
                <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">Managing Your Connection</h2>
                <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                    <div class="prose prose-sm prose-slate max-w-none dark:prose-invert">
                        <ul>
                            <li><strong>Change data types:</strong> Settings &gt; Manage Health Data Permissions</li>
                            <li><strong>View sync history:</strong> Logs tab in the app</li>
                            <li><strong>Manual sync:</strong> Dashboard &gt; Sync Now, or Settings &gt; Sync Now</li>
                            <li><strong>View health data:</strong> Settings &gt; Health Data</li>
                            <li><strong>Disconnect from the app:</strong> Settings &gt; Disconnect Account (clears all credentials, keeps Apple Health data intact)</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Troubleshooting --}}
            <section class="mb-12">
                <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">Troubleshooting</h2>
                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-slate-900 dark:text-white">Problem</th>
                                <th class="px-4 py-3 font-semibold text-slate-900 dark:text-white">What to Do</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">Sign-in fails</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">Confirm your account credentials and try signing in again from the iOS app.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">No data showing</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">Open Apple Health and verify data is being recorded. Then check your HealthKit permissions in the app settings.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">Sync fails</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">Make sure your Plate instance is online and the API is responding. Try a manual sync from Settings.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">Want to reconnect</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">Disconnect from app settings first, then sign in again. Your Apple Health data stays intact.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Back to Landing --}}
            <div class="text-center">
                <a href="{{ route('health-sync') }}" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                    &larr; Back to Acara Health Sync
                </a>
            </div>
        </div>
    </div>

    <x-footer />
</x-default-layout>
