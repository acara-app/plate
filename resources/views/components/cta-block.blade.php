@props([
    'title' => '',
    'description' => '',
    'buttonText' => '',
    'buttonUrl' => route('meet-altani'),
    'avatarSrc' => 'https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp',
])

<div class="relative max-w-6xl mx-auto overflow-hidden rounded-3xl bg-linear-to-br from-[#FFF5EE] via-[#FFFBF5] to-[#FFEFE5] p-8 shadow-sm ring-1 ring-[#FF6B4A]/10 sm:p-10">
    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-[#FF6B4A]/6 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-[#FFBFA9]/10 blur-2xl" aria-hidden="true"></div>

    <div class="relative z-10 flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-8">
        <div class="shrink-0">
            <div class="rounded-full bg-linear-to-br from-[#FF6B4A]/20 to-[#FFBFA9]/30 p-1">
                <img
                    src="{{ $avatarSrc }}"
                    alt="Altani, your personal AI health coach"
                    class="h-24 w-24 rounded-full object-cover ring-2 ring-white sm:h-28 sm:w-28"
                >
            </div>
        </div>

        <div class="flex-1 text-center sm:text-left">
            <h3 class="text-xl font-bold tracking-tight text-slate-800 sm:text-2xl">
                {{ $title }}
            </h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-500 sm:text-base">
                {{ $description }}
            </p>
            <div class="mt-6">
                <a
                    href="{{ $buttonUrl }}"
                    class="group inline-flex items-center justify-center gap-2 rounded-full bg-[#FF6B4A] px-7 py-3 text-sm font-semibold text-white shadow-md shadow-[#FF6B4A]/20 transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#E85A3A] hover:shadow-lg hover:shadow-[#FF6B4A]/25"
                >
                    {{ $buttonText }}
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
