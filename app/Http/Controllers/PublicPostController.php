<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostCategory;
use App\Models\Content;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PublicPostController
{
    public function show(Request $request): View
    {
        $locale = $request->route('locale', 'en');
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

        return view('blog.show', [
            'content' => $content,
            'translations' => $translations,
            'locale' => $locale,
        ]);
    }

    public function index(Request $request): View
    {
        $locale = $request->route('locale', 'en');

        $posts = Content::query()
            ->post()
            ->published()
            ->inLocale($locale)
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'pageTitle' => 'Blog',
            'pageDescription' => 'Practical articles on nutrition, healthy eating, and living well — powered by the latest health research.',
            'seoTitle' => 'Blog | Acara Plate',
            'seoDescription' => 'Articles on nutrition, healthy eating, meal planning, and wellness tips to help you make smarter food choices.',
            'locale' => $locale,
            'canonicalUrl' => $this->getCanonicalUrl($request, $locale),
        ]);
    }

    public function category(Request $request): View
    {
        $locale = $request->route('locale', 'en');
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

        return view('blog.index', [
            'posts' => $posts,
            'pageTitle' => $categoryEnum->title(),
            'pageDescription' => $categoryEnum->description(),
            'seoTitle' => $categoryEnum->title().' | Acara Plate',
            'seoDescription' => $categoryEnum->description(),
            'locale' => $locale,
            'canonicalUrl' => route('blog.category', ['category' => $category]),
        ]);
    }

    private function getCanonicalUrl(Request $request, string $locale): string
    {
        $page = $request->integer('page', 1);
        $params = [];
        if ($page > 1) {
            $params['page'] = $page;
        }

        if ($locale === 'en') {
            return route('blog.index', $params);
        }

        return route('blog.locale.index', array_merge(['locale' => $locale], $params));
    }
}
