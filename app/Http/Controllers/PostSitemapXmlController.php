<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Content;
use App\Utilities\LanguageUtil;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class PostSitemapXmlController
{
    public function post(): Response
    {
        $posts = Content::query()
            ->published()
            ->post()
            ->with(['translations' => fn (Relation $query) => $query->where('is_published', true)])
            ->orderByDesc('created_at')
            ->get();

        $sitemap = Sitemap::create();

        foreach (LanguageUtil::keys() as $locale) {
            $sitemap->add(
                Url::create($locale === 'en' ? route('post.index') : route('post.locale.index', ['locale' => $locale]))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        foreach ($posts as $post) {
            $locale = $post->locale ?? 'en';

            $postUrl = $locale === 'en'
                ? route('post.show', $post->slug)
                : route('post.locale.show', ['locale' => $locale, 'slug' => $post->slug]);

            $url = Url::create($postUrl)
                ->setLastModificationDate($post->updated_at)
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
