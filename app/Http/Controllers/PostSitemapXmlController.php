<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Content;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class PostSitemapXmlController
{
    private const array LOCALES = ['en', 'mn'];

    private const string TEMPLATE_UPDATED = '2026-11-22';

    public function post(): Response
    {
        $posts = Content::query()
            ->published()
            ->post()
            ->whereIn('locale', self::LOCALES)
            ->with(['translations' => fn (Relation $query) => $query->where('is_published', true)->whereIn('locale', self::LOCALES)])
            ->orderByDesc('created_at')
            ->get();

        $templateUpdated = CarbonImmutable::parse(self::TEMPLATE_UPDATED);

        $sitemap = Sitemap::create();

        foreach (self::LOCALES as $locale) {
            $indexUrl = $locale === 'en' ? route('post.index') : route('post.locale.index', ['locale' => $locale]);
            $url = Url::create($indexUrl)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8);

            foreach (self::LOCALES as $altLocale) {
                $altUrl = $altLocale === 'en' ? route('post.index') : route('post.locale.index', ['locale' => $altLocale]);
                $url->addAlternate($altUrl, $altLocale);
            }

            $url->addAlternate(route('post.index'), 'x-default');
            $sitemap->add($url);
        }

        foreach ($posts as $post) {
            $locale = $post->locale ?? 'en';

            $postUrl = $locale === 'en'
                ? route('post.show', $post->slug)
                : route('post.locale.show', ['locale' => $locale, 'slug' => $post->slug]);

            $url = Url::create($postUrl)
                ->setLastModificationDate($post->updated_at->max($templateUpdated))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.7);

            if ($post->image_url) {
                $url->addImage(
                    $post->image_url,
                    '',
                    '',
                    $post->title
                );
            }

            $url->addAlternate($postUrl, $locale);

            $otherTranslations = $post->translations->where('id', '!=', $post->id);

            if ($otherTranslations->isEmpty() && $post->translation_group === null) {
                $otherTranslations = Content::query()
                    ->post()
                    ->published()
                    ->where('slug', $post->slug)
                    ->where('locale', '!=', $post->locale)
                    ->whereIn('locale', self::LOCALES)
                    ->get();
            }

            foreach ($otherTranslations as $translation) {
                $transLocale = $translation->locale ?? 'en';
                $transUrl = $transLocale === 'en'
                    ? route('post.show', $translation->slug)
                    : route('post.locale.show', ['locale' => $transLocale, 'slug' => $translation->slug]);

                $url->addAlternate($transUrl, $transLocale);
            }

            $englishTranslation = $otherTranslations->firstWhere('locale', 'en');
            $englishSlug = $locale === 'en' || $englishTranslation === null
                ? $post->slug
                : $englishTranslation->slug;

            $url->addAlternate(route('post.show', $englishSlug), 'x-default');

            $sitemap->add($url);
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
