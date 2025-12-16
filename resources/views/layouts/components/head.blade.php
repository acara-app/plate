<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="author" content="Acara Plate" />
<meta name="google" content="notranslate" data-rh="true" />
<meta name="robots" content="index, follow" data-rh="true" />
<meta name="applicable-device" content="pc, mobile" data-rh="true" />

<title>@yield('title', 'Acara Plate - AI Diabetes Meal Planner & Glucose Tracker')</title>
<meta name="description" content="@yield('meta_description', 'Acara Plate is an AI-powered nutrition platform for diabetes management. Get personalized meal plans, track glucose levels, and achieve your health goals.')" data-rh="true" />
<meta name="keywords" content="@yield('meta_keywords', 'diabetes nutrition, AI meal planner, glucose tracking, personalized meal plans, diabetes management')" />
<link rel="canonical" href="{{ url()->current() }}" />

<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-title" content="Acara Plate" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<meta name="theme-color" content="#000000" />

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Acara Plate" />
<meta property="og:url" content="{{ url()->current() }}" data-rh="true" />
<meta property="og:title" content="@yield('title', 'Acara Plate - AI Nutrition for Diabetes')" />
<meta property="og:description" content="@yield('meta_description', 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels.')" />
<meta property="og:image" content="{{ asset('banner-acara-plate.webp') }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="Acara Plate - AI Nutrition for Diabetes Management" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:url" content="{{ url()->current() }}" />
<meta name="twitter:title" content="@yield('title', 'Acara Plate - AI Nutrition for Diabetes')" />
<meta name="twitter:description" content="@yield('meta_description', 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels.')" />
<meta name="twitter:image" content="{{ asset('banner-acara-plate.webp') }}" />
<meta name="twitter:image:alt" content="Acara Plate - AI Nutrition for Diabetes Management" />

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}" />

@livewireStyles
@vite(['resources/css/app.css', 'resources/js/app.js'])

@yield('head')
