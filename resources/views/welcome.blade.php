<x-default-layout>
    <div
        class="relative flex min-h-screen flex-col items-center overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-6 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 dark:text-slate-50">
        
        {{-- Animated background elements --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"></div>
            <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-teal-300/20 blur-3xl animation-delay-2000 dark:bg-teal-500/10"></div>
            <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 animate-pulse rounded-full bg-cyan-300/10 blur-3xl animation-delay-4000 dark:bg-cyan-500/5"></div>
        </div>

        <header class="relative z-10 mb-8 w-full max-w-[335px] not-has-[nav]:hidden lg:mb-12 lg:max-w-5xl">
            <nav class="flex items-center justify-end gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-lg border border-emerald-200 bg-white/80 px-6 py-2.5 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-emerald-300 hover:bg-white hover:shadow-md dark:border-emerald-900/50 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-emerald-800 dark:hover:bg-slate-800">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center rounded-lg px-6 py-2.5 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-white/60 hover:backdrop-blur-sm dark:text-slate-200 dark:hover:bg-slate-800/60">
                        Log in
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center rounded-lg border border-emerald-200 bg-white/80 px-6 py-2.5 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-emerald-300 hover:bg-white hover:shadow-md dark:border-emerald-900/50 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-emerald-800 dark:hover:bg-slate-800">
                        Register
                    </a>
                @endauth
            </nav>
        </header>

        <div
            class="relative z-10 flex w-full items-center justify-center opacity-100 transition-opacity duration-700 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col-reverse gap-0 lg:max-w-5xl lg:flex-row lg:gap-8">
                <div
                    class="group flex-1 rounded-b-2xl bg-white/90 p-8 shadow-2xl shadow-emerald-500/10 backdrop-blur-md transition-all duration-500 hover:shadow-emerald-500/20 lg:rounded-2xl lg:p-12 lg:pr-16 dark:bg-slate-900/90 dark:shadow-emerald-500/5 dark:hover:shadow-emerald-500/10">
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900 lg:text-5xl dark:text-white">
                            Your Personalized
                            <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent dark:from-emerald-400 dark:to-teal-400">
                                Nutrition AI
                            </span>
                            Starts Here
                        </h1>
                        
                        <p class="text-lg leading-relaxed text-slate-600 dark:text-slate-300">
                            Discover CustomNutriAI, the open-source app that crafts tailored meal plans for your dietary restrictions and goals. Answer our smart questionnaire to get nutrition advice designed just for you.
                        </p>

                        <div class="pt-4">
                            <a href="{{ route('register') }}"
                                class="group/btn inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-emerald-500/30 transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-emerald-500/40 active:scale-100 dark:shadow-emerald-500/20 dark:hover:shadow-emerald-500/30">
                                Get Your Custom Plan
                                <svg class="h-5 w-5 transition-transform duration-300 group-hover/btn:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 text-sm">
                            <div class="flex items-center gap-2.5 rounded-lg bg-emerald-50/50 p-3 dark:bg-emerald-950/30">
                                <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium text-slate-700 dark:text-slate-300">Open Source (soon)</span>
                            </div>
                            <div class="flex items-center gap-2.5 rounded-lg bg-teal-50/50 p-3 dark:bg-teal-950/30">
                                <svg class="h-5 w-5 shrink-0 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 7H7v6h6V7z" />
                                    <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium text-slate-700 dark:text-slate-300">AI-Powered</span>
                            </div>
                            <div class="flex items-center gap-2.5 rounded-lg bg-cyan-50/50 p-3 dark:bg-cyan-950/30">
                                <svg class="h-5 w-5 shrink-0 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium text-slate-700 dark:text-slate-300">Personalized</span>
                            </div>
                            <div class="flex items-center gap-2.5 rounded-lg bg-purple-50/50 p-3 dark:bg-purple-950/30">
                                <svg class="h-5 w-5 shrink-0 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium text-slate-700 dark:text-slate-300">Privacy First</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="relative aspect-[335/376] w-full shrink-0 overflow-hidden rounded-t-2xl bg-gradient-to-br from-emerald-100 via-teal-50 to-cyan-100 shadow-2xl shadow-emerald-500/10 lg:aspect-auto lg:w-[480px] lg:rounded-2xl dark:from-emerald-950 dark:via-teal-950 dark:to-cyan-950 dark:shadow-emerald-500/5">
                    {{-- Decorative pattern --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-teal-500/10"></div>
                    
                    {{-- Floating nutrition elements --}}
                    <div class="absolute inset-0 flex items-center justify-center p-8">
                        <div class="relative h-full w-full">
                            {{-- Animated floating cards --}}
                            <div class="absolute left-4 top-8 animate-float rounded-xl bg-white/90 p-4 shadow-lg backdrop-blur-sm dark:bg-slate-800/90">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/50">
                                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Daily Goal</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">2,000 cal</div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute right-4 top-20 animate-float rounded-xl bg-white/90 p-4 shadow-lg backdrop-blur-sm animation-delay-2000 dark:bg-slate-800/90">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-teal-100 p-2 dark:bg-teal-900/50">
                                        <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Meal Time</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">12:30 PM</div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute bottom-16 left-8 animate-float rounded-xl bg-white/90 p-4 shadow-lg backdrop-blur-sm animation-delay-4000 dark:bg-slate-800/90">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-cyan-100 p-2 dark:bg-cyan-900/50">
                                        <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Protein</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">85g</div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute bottom-8 right-8 animate-float rounded-xl bg-white/90 p-4 shadow-lg backdrop-blur-sm animation-delay-1000 dark:bg-slate-800/90">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/50">
                                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Health Score</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">92/100</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Center element --}}
                            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform">
                                <div class="rounded-2xl bg-white/95 p-6 shadow-2xl backdrop-blur-md dark:bg-slate-800/95">
                                    <div class="text-center">
                                        <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-lg">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                        </div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Smart AI</div>
                                        <div class="text-lg font-bold text-slate-900 dark:text-white">Analysis</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <style>
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animation-delay-1000 {
            animation-delay: 1s;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</x-default-layout>
