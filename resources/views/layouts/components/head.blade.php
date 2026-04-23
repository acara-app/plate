<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="author" content="Acara Plate" />
<meta name="google" content="notranslate" data-rh="true" />
<meta name="robots" content="index, follow" data-rh="true" />
<meta name="applicable-device" content="pc, mobile" data-rh="true" />

@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ $title ?? 'Acara Plate - AI Diabetes Meal Planner & Glucose Tracker' }}</title>
@endif

@hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')" data-rh="true" />
@else
    <meta name="description"
        content="{{ $metaDescription ?? 'Acara Plate is an AI-powered nutrition platform for diabetes management. Get personalized meal plans, track glucose levels, and achieve your health goals.' }}"
        data-rh="true" />
@endif

@hasSection('meta_keywords')
    <meta name="keywords" content="@yield('meta_keywords')" />
@else
    <meta name="keywords"
        content="{{ $metaKeywords ?? 'diabetes nutrition, AI meal planner, glucose tracking, personalized meal plans, diabetes management, blood sugar tracking, diabetic meal planning, AI nutrition assistant' }}" />
@endif

<link rel="canonical" href="@yield('canonical_url', strtok(url()->current(), '?'))" />

<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-title" content="Acara Plate" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<meta name="theme-color" content="#000000" />

<!-- Open Graph / Facebook -->
<meta property="og:type" content="@yield('og_type', 'website')" />
<meta property="og:locale" content="@yield('og_locale', 'en_US')" />
<meta property="og:site_name" content="Acara Plate" />
<meta property="og:url" content="{{ url()->current() }}" data-rh="true" />
@hasSection('title')
    <meta property="og:title" content="@yield('title')" />
@else
    <meta property="og:title" content="{{ $title ?? 'Acara Plate - AI Nutrition for Diabetes' }}" />
@endif
@hasSection('meta_description')
    <meta property="og:description" content="@yield('meta_description')" />
@else
    <meta property="og:description"
        content="{{ $metaDescription ?? 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels to achieve your health goals.' }}" />
@endif
<meta property="og:image" content="@yield('og_image', asset('banner-acara-plate.webp'))" />
<meta property="og:image:width" content="@yield('og_image_width', '1200')" />
<meta property="og:image:height" content="@yield('og_image_height', '630')" />
<meta property="og:image:alt" content="@yield('og_image_alt', 'Acara Plate - AI Nutrition for Diabetes Management')" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:url" content="{{ url()->current() }}" />
@hasSection('title')
    <meta name="twitter:title" content="@yield('title')" />
@else
    <meta name="twitter:title" content="{{ $title ?? 'Acara Plate - AI Nutrition for Diabetes' }}" />
@endif
@hasSection('meta_description')
    <meta name="twitter:description" content="@yield('meta_description')" />
@else
    <meta name="twitter:description"
        content="{{ $metaDescription ?? 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels to achieve your health goals.' }}" />
@endif
<meta name="twitter:image" content="@yield('og_image', asset('banner-acara-plate.webp'))" />
<meta name="twitter:image:alt" content="@yield('og_image_alt', 'Acara Plate - AI Nutrition for Diabetes Management')" />

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}" />
<link rel="manifest" href="/build/manifest.webmanifest" />

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500" rel="stylesheet" />

@livewireStyles

@vite(['resources/css/app.css', 'resources/js/app.js'])

@yield('head')
@stack('head')

@production
    <script defer src="https://cloud.umami.is/script.js" data-website-id="00659ffa-f13b-411a-81a7-76d2bd81d2c6"></script>
@endproduction

@stack('turnstile')
