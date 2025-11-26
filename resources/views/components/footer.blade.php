<footer class="border-t dark:border-gray-800 border-gray-300 w-full">
    <div class="mx-auto max-w-7xl overflow-hidden px-6 py-16 sm:py-24 lg:px-8">
        <nav
            class="-mb-6 columns-2 sm:flex sm:justify-center sm:space-x-12"
            aria-label="Footer"
        >
            <div class="pb-6">
                <a
                    href="{{ route('terms') }}"
                    class="text-sm leading-6 dark:text-slate-400 text-slate-500 dark:hover:text-slate-200 hover:text-slate-950"
                    >Terms</a
                >
            </div>
            <div class="pb-6">
                <a
                    href="{{ route('privacy') }}"
                    class="text-sm leading-6 dark:text-slate-400 text-slate-500 dark:hover:text-slate-200 hover:text-slate-950"
                    >Privacy Policy</a
                >
            </div>
            <div class="pb-6">
                <a
                    href="{{ route('support') }}"
                    class="text-sm leading-6 dark:text-slate-400 text-slate-500 dark:hover:text-slate-200 hover:text-slate-950"
                    >Support</a
                >
            </div>
            <div class="pb-6">
                <a
                    href="{{ route('install-app') }}"
                    class="text-sm leading-6 dark:text-slate-400 text-slate-500 dark:hover:text-slate-200 hover:text-slate-950"
                    >Install App</a
                >
            </div>
        </nav>

        <div class="mt-10 flex space-x-10 sm:justify-center">
            <a
                href="https://github.com/acara-app/plate"
                class="dark:text-slate-400 text-slate-500 dark:hover:text-slate-200 hover:text-slate-950"
            >
                <span class="sr-only">GitHub</span>

                <x-icons.github class="h-6 w-6" />
            </a>
        </div>

        <div class="mt-8 border-t border-gray-200 dark:border-gray-800 pt-8 text-center">
            <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Disclaimer:</span> Acara Plate is an AI-powered tool for informational purposes only and does not provide medical advice. Always consult a healthcare professional for medical concerns.
            </p>
            <p class="mt-4 text-xs leading-5 dark:text-slate-400 text-slate-500">&copy; {{ date('Y') }} Acara Plate.</p>
        </div>
    </div>

    <livewire:views.create />
</footer>
