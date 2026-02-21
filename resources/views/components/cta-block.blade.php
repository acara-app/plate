@props([
    'title' => '',
    'description' => '',
    'buttonText' => '',
    'buttonUrl' => 'meet-altani',
    'avatarSrc' => 'https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp',
])

<div class="rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-200 sm:p-8 my-8 max-w-6xl mx-auto">
    <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-8">
        <div class="shrink-0">
            <img
                src="{{ $avatarSrc }}"
                alt="Altani, your personal AI health coach"
                class="mx-auto h-24 w-24 rounded-full object-cover shadow-sm ring-4 ring-slate-50 sm:h-28 sm:w-28"
            >
        </div>
        <div class="flex-1 text-center sm:text-left">
            <h3 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                {{ $title }}
            </h3>
            <p class="mt-2 text-sm text-slate-600 sm:text-base">
                {{ $description }}
            </p>
            <div class="mt-6 sm:mt-5">
                <a
                    href="{{ route($buttonUrl) }}"
                    class="inline-flex items-center justify-center rounded-full bg-[#FF6B4A] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-[#E85A3A] hover:shadow-md"
                >
                    {{ $buttonText }}
                    <svg class="ml-2 -mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
