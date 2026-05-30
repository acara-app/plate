<?php

declare(strict_types=1);

it('marks redesigned ai and blog pages as recently modified in the static sitemap', function (): void {
    $sitemap = simplexml_load_file(public_path('sitemap.xml'));

    expect($sitemap)->not->toBeFalse();

    $namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    $lastModifiedDates = collect();

    foreach ($sitemap->children($namespace)->url as $url) {
        $children = $url->children($namespace);

        $lastModifiedDates->put((string) $children->loc, (string) $children->lastmod);
    }

    expect($lastModifiedDates)
        ->toHaveKey('https://plate.acara.app/ai-nutritionist', '2026-05-30')
        ->toHaveKey('https://plate.acara.app/ai-health-coach', '2026-05-30')
        ->toHaveKey('https://plate.acara.app/post', '2026-05-30');
});
