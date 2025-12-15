<meta charset="UTF-8" />
<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
/>
<meta
    name="csrf-token"
    content="{{ csrf_token() }}"
/>
<meta
    name="author"
    content="Acara Plate"
/>
<meta
    name="google"
    content="notranslate"
    data-rh="true"
/>
<meta
    name="robots"
    content="index, follow"
    data-rh="true"
/>
<meta
    name="description"
    content="{{ config('app.name', 'Pinkary') }} - One Link. All Your Socials."
    data-rh="true"
/>
<meta
    name="applicable-device"
    content="pc, mobile"
    data-rh="true"
/>
<meta
    name="canonical"
    content="{{ url()->current() }}"
    data-rh="true"
/>

<meta
    name="mobile-web-app-capable"
    content="yes"
/>
<meta
    name="apple-mobile-web-app-title"
    content="Pinkary"
/>
<meta
    name="apple-mobile-web-app-status-bar-style"
    content="black"
/>

<meta
    name="theme-color"
    content="#000000"
/>

<meta
    content="Pinkary"
    property="og:site_name"
/>
<meta
    property="og:url"
    content="{{ url()->current() }}"
    data-rh="true"
/>

@livewireStyles
@vite(['resources/css/app.css', 'resources/js/app.js'])

@yield('head')
