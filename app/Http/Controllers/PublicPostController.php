<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostCategory;
use App\Models\Content;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PublicPostController
{
    public function show(Request $request): View
    {
        $locale = $request->route('locale', 'en');
        app()->setLocale($locale);
        $slug = $request->route('slug');

        $content = Content::query()
            ->post()
            ->published()
            ->inLocale($locale)
            ->where('slug', $slug)
            ->first();

        throw_unless($content, NotFoundHttpException::class, 'Post not found');

        $translations = $this->getTranslations($content);

        $xDefaultUrl = $this->getXDefaultUrl($content, $translations);

        return view('post.show', [
            'content' => $content,
            'translations' => $translations,
            'locale' => $locale,
            'xDefaultUrl' => $xDefaultUrl,
        ]);
    }

    public function index(Request $request): View
    {
        $locale = $request->route('locale', 'en');
        app()->setLocale($locale);

        $posts = Content::query()
            ->post()
            ->published()
            ->inLocale($locale)
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('post.index', [
            'posts' => $posts,
            'pageTitle' => __('post.page_title'),
            'pageDescription' => __('post.page_description'),
            'seoTitle' => __('post.seo_title'),
            'seoDescription' => __('post.seo_description'),
            'locale' => $locale,
            'canonicalUrl' => $this->getCanonicalUrl($request, $locale),
            'hreflangLinks' => $this->getHreflangLinks('post.index', 'post.locale.index'),
            'xDefaultUrl' => $this->getXDefaultUrlForIndex('post.index'),
        ]);
    }

    public function category(Request $request): View
    {
        $locale = $request->route('locale', 'en');
        app()->setLocale($locale);
        $category = $request->route('category');

        $categoryEnum = PostCategory::tryFrom($category);
        throw_unless($categoryEnum, NotFoundHttpException::class, 'Category not found');

        $posts = Content::query()
            ->post()
            ->published()
            ->inLocale($locale)
            ->inCategory($categoryEnum)
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('post.index', [
            'posts' => $posts,
            'pageTitle' => $categoryEnum->title(),
            'pageDescription' => $categoryEnum->description(),
            'seoTitle' => $categoryEnum->title().' | Acara Plate',
            'seoDescription' => $categoryEnum->description(),
            'locale' => $locale,
            'canonicalUrl' => $this->getCanonicalUrl($request, $locale, 'post.category', 'post.locale.category', ['category' => $category]),
            'hreflangLinks' => $this->getHreflangLinks(
                'post.category',
                'post.locale.category',
                ['category' => $category],
            ),
            'xDefaultUrl' => route('post.category', ['category' => $category]),
        ]);
    }

    /**
     * @return Collection<int, Content>
     */
    private function getTranslations(Content $content): Collection
    {
        if ($content->translation_group !== null) {
            return $content->translations
                ->where('id', '!=', $content->id)
                ->where('is_published', true)
                ->values();
        }

        return Content::query()
            ->post()
            ->published()
            ->where('slug', $content->slug)
            ->where('locale', '!=', $content->locale)
            ->get()
            ->values();
    }

    /**
     * @param  Collection<int, Content>  $translations
     */
    private function getXDefaultUrl(Content $content, Collection $translations): string
    {
        if ($content->locale === 'en') {
            return route('post.show', $content->slug);
        }

        $englishTranslation = $translations->firstWhere('locale', 'en');

        if ($englishTranslation instanceof Content) {
            return route('post.show', $englishTranslation->slug);
        }

        return route('post.index');
    }

    private function getXDefaultUrlForIndex(string $enRoute): string
    {
        return route($enRoute);
    }

    /**
     * @param  array<string, string>  $extraParams
     * @return array<int, array{locale: string, url: string}>
     */
    private function getHreflangLinks(string $enRoute, string $localeRoute, array $extraParams = []): array
    {
        $links = [];

        foreach (LanguageUtil::keys() as $hrefLocale) {
            $links[] = [
                'locale' => $hrefLocale,
                'url' => $hrefLocale === 'en'
                    ? route($enRoute, $extraParams)
                    : route($localeRoute, array_merge(['locale' => $hrefLocale], $extraParams)),
            ];
        }

        return $links;
    }

    /**
     * @param  array<string, string>  $extraParams
     */
    private function getCanonicalUrl(Request $request, string $locale, string $enRoute = 'post.index', string $localeRoute = 'post.locale.index', array $extraParams = []): string
    {
        $page = $request->integer('page', 1);
        $params = $extraParams;
        if ($page > 1) {
            $params['page'] = $page;
        }

        if ($locale === 'en') {
            return route($enRoute, $params);
        }

        return route($localeRoute, array_merge(['locale' => $locale], $params));
    }
}
