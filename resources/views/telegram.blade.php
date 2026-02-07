@section('title', 'Connect on Telegram | Chat with Your AI Nutritionist')
@section('meta_description', 'Connect with Acara Plate on Telegram to get personalized nutrition advice, meal suggestions, and glucose predictions directly in your chats.')

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
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="mb-2">Connect on Telegram</h1>
                        <p class="text-lg text-slate-600 dark:text-slate-400 m-0">
                            Chat with your AI nutritionist anywhere
                        </p>
                    </div>
                </div>

                <p>
                    Get personalized nutrition advice, meal suggestions, and glucose predictions directly in Telegram. 
                    Your AI nutritionist is available 24/7, right in your pocket.
                </p>

                <div class="not-prose my-8 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-8">
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">
                        How to Connect
                    </h2>

                    <ol class="space-y-6 text-slate-700 dark:text-slate-300">
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">1</span>
                            <div>
                                <strong class="text-slate-900 dark:text-white">Open Telegram</strong>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">Search for <strong>@AcaraPlate_bot</strong> or click the button below</p>
                            </div>
                        </li>

                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">2</span>
                            <div>
                                <strong class="text-slate-900 dark:text-white">Start the Bot</strong>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">Tap "Start" or send <code>/start</code> to begin</p>
                            </div>
                        </li>

                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">3</span>
                            <div>
                                <strong class="text-slate-900 dark:text-white">Link Your Account</strong>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">Go to <a href="{{ route('integrations.edit') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Settings → Integrations</a> and generate a linking token</p>
                            </div>
                        </li>

                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">4</span>
                            <div>
                                <strong class="text-slate-900 dark:text-white">Start Chatting!</strong>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">Send the token to the bot and begin your nutrition journey</p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <a
                            href="https://t.me/AcaraPlate_bot"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-white font-medium hover:bg-blue-700 transition-colors"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            Open @AcaraPlate_bot
                        </a>

                        @auth
                            <a
                                href="{{ route('integrations.edit') }}"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-3 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            >
                                Go to Settings
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-3 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            >
                                Create Account
                            </a>
                        @endauth
                    </div>
                </div>

                <h2>What You Can Do</h2>

                <ul>
                    <li><strong>Ask anything</strong> — "What should I eat for breakfast?" or "Will pizza spike my glucose?"</li>
                    <li><strong>Get meal plans</strong> — Request personalized meal plans on the go</li>
                    <li><strong>Restaurant help</strong> — "I'm at Chipotle, what should I order?"</li>
                    <li><strong>Glucose predictions</strong> — Understand how foods affect your blood sugar</li>
                    <li><strong>24/7 access</strong> — Your AI nutritionist is always available</li>
                </ul>

                <div class="mt-8 rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Available Commands</h3>
                    <dl class="grid gap-3 text-sm">
                        <div class="flex gap-4">
                            <dt class="font-mono text-blue-600 dark:text-blue-400 w-32">/start</dt>
                            <dd class="text-slate-600 dark:text-slate-400">Welcome message and setup instructions</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="font-mono text-blue-600 dark:text-blue-400 w-32">/link &lt;token&gt;</dt>
                            <dd class="text-slate-600 dark:text-slate-400">Link your Telegram to your account</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="font-mono text-blue-600 dark:text-blue-400 w-32">/me</dt>
                            <dd class="text-slate-600 dark:text-slate-400">Show your profile information</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="font-mono text-blue-600 dark:text-blue-400 w-32">/help</dt>
                            <dd class="text-slate-600 dark:text-slate-400">Show available commands</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        Need help? <a href="{{ route('support') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Contact support</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
