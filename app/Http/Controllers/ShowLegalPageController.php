<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LegalPage;
use App\Utilities\LocalizedDate;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ShowLegalPageController
{
    /**
     * @var list<string>
     */
    public const array TRANSLATIONS = ['fr', 'mn'];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $page = LegalPage::from(is_string($request->route('page')) ? $request->route('page') : '');
        $locale = $request->route('locale');
        $locale = is_string($locale) ? $locale : 'en';

        if ($locale === 'en' && $request->query('lang') !== 'en' && in_array(app()->getLocale(), self::TRANSLATIONS, true)) {
            return to_route("{$page->routeName()}.locale", ['locale' => app()->getLocale()]);
        }

        app()->setLocale($locale);

        return view('legal-page', [
            'page' => $page,
            'locale' => $locale,
            'ogLocale' => match ($locale) {
                'fr' => 'fr_CA',
                'mn' => 'mn_MN',
                default => 'en_US',
            },
            'effectiveDate' => LocalizedDate::format(CarbonImmutable::parse(config()->string($page->versionConfigKey())), $locale),
            'alternates' => [
                'en' => route($page->routeName()),
                ...array_combine(self::TRANSLATIONS, array_map(fn (string $translation): string => route("{$page->routeName()}.locale", ['locale' => $translation]), self::TRANSLATIONS)),
            ],
        ]);
    }
}
