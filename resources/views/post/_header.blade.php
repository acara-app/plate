<header class="sticky top-0 z-50 w-full py-4 px-5 sm:px-6 lg:px-8 flex justify-between items-center bg-[#f7f3ed]/90 backdrop-blur-md border-b border-dashed border-[#1a1a1a]/10">
    <a href="/" class="flex items-center gap-2.5 text-lg font-bold text-[#1a1a1a]">
        <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
        <span class="font-display tracking-tight">Acara Plate</span>
    </a>
    <div class="flex items-center gap-4">
        <a href="{{ route('login') }}" class="text-sm font-medium text-[#1a1a1a]/60 hover:text-[#1b4332] transition-colors font-serif">
            {{ __('post.log_in') }}
        </a>
        <a href="{{ route('register') }}" class="rounded-full bg-[#1a1a1a] px-5 py-2 text-sm font-semibold text-[#f7f3ed] hover:bg-[#1b4332] transition-all duration-200 font-mono text-xs uppercase tracking-wider">
            {{ __('post.get_started') }}
        </a>
    </div>
</header>
