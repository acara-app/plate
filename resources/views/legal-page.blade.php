@section('title', __("common.legal_pages.{$page->translationKey()}.title"))
@section('meta_description', __("common.legal_pages.{$page->translationKey()}.meta_description"))
@section('og_locale', $ogLocale)

@push('head')
    @foreach ($alternates as $hreflang => $url)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alternates['en'] }}" />
@endpush

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <div class="-mt-10 mb-12 flex items-center justify-between gap-4">
            <a
                href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
                class="flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
                wire:navigate
            >
                <x-icons.chevron-left class="size-4" />
                <span>{{ __('common.legal_pages.back') }}</span>
            </a>
            <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1">
                @foreach ($alternates as $language => $url)
                    @continue($language === $locale)
                    <a href="{{ $language === 'en' ? route($page->routeName(), ['lang' => 'en']) : $url }}" hreflang="{{ $language }}" lang="{{ $language }}" class="text-sm dark:text-slate-400 text-slate-600 underline underline-offset-4 hover:text-slate-900 dark:hover:text-slate-200">{{ __('common.legal_pages.read_in', locale: $language) }}</a>
                @endforeach
            </div>
        </div>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                @include("{$page->value}.{$locale}")
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
