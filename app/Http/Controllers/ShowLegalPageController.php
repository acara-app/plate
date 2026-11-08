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
            'effectiveDate' => $this->formatDate(CarbonImmutable::parse(config()->string($page->versionConfigKey())), $locale),
            'alternates' => [
                'en' => route($page->routeName()),
                ...array_combine(self::TRANSLATIONS, array_map(fn (string $translation): string => route("{$page->routeName()}.locale", ['locale' => $translation]), self::TRANSLATIONS)),
            ],
        ]);
    }

    private function formatDate(CarbonImmutable $date, string $locale): string
    {
        if ($locale === 'mn') {
            $monthOrdinal = in_array($date->month, [1, 4, 9, 11], true) ? 'дүгээр' : 'дугаар';
            $daySuffix = in_array($date->day % 10, [1, 4, 9], true) ? 'ний' : 'ны';

            return "{$date->year} оны {$date->month} {$monthOrdinal} сарын {$date->day}-{$daySuffix} өдөр";
        }

        if ($locale === 'en') {
            return $date->format('F j, Y');
        }

        return $date->settings(['locale' => $locale])->translatedFormat('j F Y');
    }
}
