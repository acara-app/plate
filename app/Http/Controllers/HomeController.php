<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SeoLinkManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class HomeController
{
    public function __construct(private SeoLinkManager $seoLinkManager) {}

    public function __invoke(Request $request): View
    {
        $featuredFoods = $this->seoLinkManager->getFeaturedFoods();

        return view('welcome', [
            'featuredFoods' => $featuredFoods,
            'canUseApp' => $request->user()?->canUseApp() === true,
        ]);
    }
}
