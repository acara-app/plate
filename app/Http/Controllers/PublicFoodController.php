<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContentType;
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

    public function index(): View
    {
        $foods = Content::query()
            ->food()
            ->published()
            ->orderBy('title')
            ->paginate(24);

        return view('food.index', [
            'foods' => $foods,
        ]);
    }
}
