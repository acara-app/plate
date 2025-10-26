<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                <h2>Support</h2>
                <p>
                    If you have any questions, concerns, or need assistance, please reach out to our support team:
                </p>
                <ul>
                    <li><strong>Email:</strong> team@acara.app</li>
                </ul>
            </div>
        </div>
    </div>
</x-default-layout>
