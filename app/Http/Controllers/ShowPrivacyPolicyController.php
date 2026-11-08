<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ShowPrivacyPolicyController
{
    /**
     * @var list<string>
     */
    public const array TRANSLATIONS = ['fr'];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $locale = $request->route('locale');
        $locale = is_string($locale) ? $locale : 'en';

        if ($locale === 'en' && $request->query('lang') !== 'en' && in_array(app()->getLocale(), self::TRANSLATIONS, true)) {
            return to_route('privacy.locale', ['locale' => app()->getLocale()]);
        }

        app()->setLocale($locale);

        $effectiveDate = CarbonImmutable::parse(config()->string('plate.privacy_policy_version'))->settings(['locale' => $locale]);

        return view('privacy-policy', [
            'locale' => $locale,
            'effectiveDate' => $locale === 'en' ? $effectiveDate->format('F j, Y') : $effectiveDate->translatedFormat('j F Y'),
            'alternates' => [
                'en' => route('privacy'),
                ...array_combine(self::TRANSLATIONS, array_map(fn (string $translation): string => route('privacy.locale', ['locale' => $translation]), self::TRANSLATIONS)),
            ],
        ]);
    }
}
