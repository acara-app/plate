@php
$webPage = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'AI Accuracy & Limitations',
    'description' => 'An honest breakdown of how Acara Plate\'s AI food photo analysis works, how accurate it realistically is, its known limitations, what the confidence score means, and what happens to your photos.',
    'url' => url()->current(),
    'dateModified' => App\Services\AiTransparency::LAST_REVIEWED,
    'inLanguage' => 'en',
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'Acara Plate',
        'url' => url('/'),
    ],
];

$breadcrumbs = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'AI Accuracy & Limitations',
            'item' => url()->current(),
        ],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($webPage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
