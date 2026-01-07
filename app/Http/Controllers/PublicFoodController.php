<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Models\Content;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicFoodController
{
    public function show(Request $request, string $slug): View
    {
        $content = Content::query()
            ->where('type', ContentType::Food)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        throw_unless($content, NotFoundHttpException::class, 'Food not found');

        return view('food.show', [
            'content' => $content,
            'nutrition' => $content->nutrition,
            'displayName' => $content->display_name,
            'diabeticInsight' => $content->diabetic_insight,
            'glycemicAssessment' => $content->glycemic_assessment,
            'glycemicLoad' => $content->glycemic_load,
        ]);
    }

    public function index(Request $request): View
    {
        $query = Content::query()
            ->food()
            ->published();

        $search = $request->input('search');
        $assessment = $request->input('assessment');
        $category = $request->input('category');

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        if ($assessment && in_array($assessment, ['low', 'medium', 'high'], true)) {
            $query->whereRaw("body->>'glycemic_assessment' = ?", [$assessment]);
        }

        if ($category) {
            $categoryEnum = FoodCategory::tryFrom($category);
            if ($categoryEnum) {
                $query->inCategory($categoryEnum);
            }
        }

        $foods = $query->orderBy('title')->paginate(4)->withQueryString();

        // Get available categories for filter dropdown
        $categories = Content::food()
            ->published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($cat) => FoodCategory::tryFrom($cat))
            ->filter()
            ->sortBy(fn (FoodCategory $cat) => $cat->order());

        // Group by category when no filters applied
        $foodsByCategory = null;
        if (! $request->hasAny(['search', 'assessment', 'category'])) {
            $allFoods = Content::food()
                ->published()
                ->orderBy('category')
                ->orderBy('title')
                ->get();

            $foodsByCategory = $allFoods->groupBy(function ($food) {
                return $food->category?->value ?? 'uncategorized';
            })->sortKeys();
        }

        // Hardcoded popular comparisons for SEO internal linking
        $comparisons = [
            ['slug1' => 'brown-rice', 'name1' => 'Brown Rice', 'slug2' => 'white-rice', 'name2' => 'White Rice'],
            ['slug1' => 'apple', 'name1' => 'Apple', 'slug2' => 'banana', 'name2' => 'Banana'],
            ['slug1' => 'unsweetened-almond-milk', 'name1' => 'Almond Milk', 'slug2' => 'whole-milk', 'name2' => 'Cow Milk'],
        ];

        return view('food.index', [
            'foods' => $foods,
            'foodsByCategory' => $foodsByCategory,
            'categories' => $categories,
            'categoryOptions' => FoodCategory::options(),
            'currentSearch' => $search,
            'currentAssessment' => $assessment,
            'currentCategory' => $category,
            'comparisons' => $comparisons,
            'canonicalUrl' => $this->getCanonicalUrl($request),
        ]);
    }

    /**
     * Generate canonical URL - strips filter params, keeps only page if > 1.
     */
    private function getCanonicalUrl(Request $request): string
    {
        $page = (int) $request->input('page', 1);

        if ($page > 1) {
            return route('food.index', ['page' => $page]);
        }

        return route('food.index');
    }
}
