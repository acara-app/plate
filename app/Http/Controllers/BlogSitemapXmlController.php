<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class BlogSitemapXmlController
{
    private const array SUPPORTED_LOCALES = ['en', 'mn', 'fr'];

    public function blog(): Response
    {
        $posts = Content::query()
            ->published()
            ->post()
            ->with(['translations' => fn (Relation $query) => $query->where('is_published', true)])
            ->orderByDesc('created_at')
            ->get();

        $sitemap = Sitemap::create();

        foreach (self::SUPPORTED_LOCALES as $locale) {
            $sitemap->add(
                Url::create($locale === 'en' ? route('blog.index') : route('blog.locale.index', ['locale' => $locale]))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        foreach ($posts as $post) {
            $locale = $post->locale ?? 'en';

            $url = Url::create(
                $locale === 'en'
                    ? route('blog.show', $post->slug)
                    : route('blog.locale.show', ['locale' => $locale, 'slug' => $post->slug])
            )
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

            $otherTranslations = $post->translations->where('id', '!=', $post->id);

            foreach ($otherTranslations as $translation) {
                $transLocale = $translation->locale ?? 'en';
                $transUrl = $transLocale === 'en'
                    ? route('blog.show', $translation->slug)
                    : route('blog.locale.show', ['locale' => $transLocale, 'slug' => $translation->slug]);

                $url->addAlternate($transLocale, $transUrl);
            }

            $url->addAlternate('x-default', route('blog.show', $post->slug));

            $sitemap->add($url);
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
