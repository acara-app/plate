<header class="sticky top-0 z-50 w-full py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-100 dark:border-slate-800">
    <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white">
        <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
        <span>Acara Plate</span>
    </a>
    <div class="flex items-center gap-4">
        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">{{ __('post.log_in') }}</a>
        <a href="{{ route('register') }}" class="rounded-full bg-slate-900 dark:bg-white px-5 py-2 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition-all">
            {{ __('post.get_started') }}
        </a>
    </div>
</header>
