<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostCategory;
use App\Models\Content;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
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

        $translations = $content->translations
            ->where('id', '!=', $content->id)
            ->where('is_published', true)
            ->values();

        return view('post.show', [
            'content' => $content,
            'translations' => $translations,
            'locale' => $locale,
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
            'canonicalUrl' => $locale === 'en'
                ? route('post.category', ['category' => $category])
                : route('post.locale.category', ['locale' => $locale, 'category' => $category]),
            'hreflangLinks' => $this->getHreflangLinks(
                'post.category',
                'post.locale.category',
                ['category' => $category],
            ),
        ]);
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

    private function getCanonicalUrl(Request $request, string $locale): string
    {
        $page = $request->integer('page', 1);
        $params = [];
        if ($page > 1) {
            $params['page'] = $page;
        }

        if ($locale === 'en') {
            return route('post.index', $params);
        }

        return route('post.locale.index', array_merge(['locale' => $locale], $params));
    }
}
