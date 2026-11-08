@section('title', __('common.privacy_policy_page.title'))
@section('meta_description', __('common.privacy_policy_page.meta_description'))
@section('og_locale', $locale === 'en' ? 'en_US' : "{$locale}_CA")

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
                <span>{{ __('common.privacy_policy_page.back') }}</span>
            </a>
            @if ($locale === 'en')
                <a href="{{ $alternates['fr'] }}" hreflang="fr" lang="fr" class="text-sm dark:text-slate-400 text-slate-600 underline underline-offset-4 hover:text-slate-900 dark:hover:text-slate-200">Lire en français</a>
            @else
                <a href="{{ route('privacy', ['lang' => 'en']) }}" hreflang="en" lang="en" class="text-sm dark:text-slate-400 text-slate-600 underline underline-offset-4 hover:text-slate-900 dark:hover:text-slate-200">Read in English</a>
            @endif
        </div>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                @include("privacy-policy.{$locale}")
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
