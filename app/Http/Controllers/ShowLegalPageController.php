<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LegalPage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ShowLegalPageController
{
    /**
     * @var list<string>
     */
    public const array TRANSLATIONS = ['fr'];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $page = LegalPage::from(is_string($request->route('page')) ? $request->route('page') : '');
        $locale = $request->route('locale');
        $locale = is_string($locale) ? $locale : 'en';

        if ($locale === 'en' && $request->query('lang') !== 'en' && in_array(app()->getLocale(), self::TRANSLATIONS, true)) {
            return to_route("{$page->routeName()}.locale", ['locale' => app()->getLocale()]);
        }

        app()->setLocale($locale);

        $effectiveDate = CarbonImmutable::parse(config()->string($page->versionConfigKey()))->settings(['locale' => $locale]);

        return view('legal-page', [
            'page' => $page,
            'locale' => $locale,
            'effectiveDate' => $locale === 'en' ? $effectiveDate->format('F j, Y') : $effectiveDate->translatedFormat('j F Y'),
            'alternates' => [
                'en' => route($page->routeName()),
                ...array_combine(self::TRANSLATIONS, array_map(fn (string $translation): string => route("{$page->routeName()}.locale", ['locale' => $translation]), self::TRANSLATIONS)),
            ],
        ]);
    }
}
