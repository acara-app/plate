@props([
    'title' => '',
    'description' => '',
    'buttonText' => '',
    'buttonUrl' => 'meet-altani',
    'avatarSrc' => 'https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp',
])

<section class="bg-orange-50 py-12 sm:py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-orange-100 sm:p-10">
            <div class="items-center gap-8 lg:flex">
                <div class="mb-6 shrink-0 lg:mb-0">
                    <img
                        src="{{ $avatarSrc }}"
                        alt="Altani, your personal AI health coach"
                        class="mx-auto h-24 w-24 rounded-full object-cover shadow-lg ring-4 ring-orange-50 sm:h-32 sm:w-32"
                    >
                </div>
                <div class="flex-1 text-center lg:text-left">
                    <h3 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                        {{ $title }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 sm:text-base">
                        {{ $description }}
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route($buttonUrl) }}"
                            class="inline-flex items-center justify-center rounded-full bg-[#FF6B4A] px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-[#E85A3A] hover:shadow-lg hover:-translate-y-0.5"
                        >
                            {{ $buttonText }}
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
