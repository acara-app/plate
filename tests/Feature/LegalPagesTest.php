<?php

declare(strict_types=1);

use App\Enums\LegalPage;
use App\Http\Controllers\ShowLegalPageController;
use App\Models\User;

covers(ShowLegalPageController::class, LegalPage::class);

beforeEach(function (): void {
    config([
        'plate.privacy_policy_version' => '2026-11-08',
        'plate.terms_version' => '2026-11-08',
    ]);
});

it('serves each translation at its own URL, linked both ways with the English page', function (string $route, string $englishDate, string $locale, string $date): void {
    $english = $this->get(route($route))
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee($englishDate)
        ->assertSee("hreflang=\"{$locale}\" href=\"".route("{$route}.locale", ['locale' => $locale]).'"', false);

    $translation = $this->get(route("{$route}.locale", ['locale' => $locale]))
        ->assertOk()
        ->assertSee("<html lang=\"{$locale}\"", false)
        ->assertSee($date, false)
        ->assertSee('hreflang="en" href="'.route($route).'"', false);

    expect(mb_substr_count((string) $translation->getContent(), '<h2>'))->toBe(mb_substr_count((string) $english->getContent(), '<h2>'));
})->with([
    'privacy policy in French' => ['privacy', 'Effective Date: November 8, 2026', 'fr', "Date d'entrée en vigueur : 8 novembre 2026"],
    'privacy policy in Mongolian' => ['privacy', 'Effective Date: November 8, 2026', 'mn', 'Хүчин төгөлдөр болсон огноо: 2026 оны 11 дүгээр сарын 8-ны өдөр'],
    'terms of service in French' => ['terms', 'Last Updated: November 8, 2026', 'fr', 'Dernière mise à jour : 8 novembre 2026'],
    'terms of service in Mongolian' => ['terms', 'Last Updated: November 8, 2026', 'mn', 'Сүүлд шинэчилсэн огноо: 2026 оны 11 дүгээр сарын 8-ны өдөр'],
]);

it('writes Mongolian dates with the endings official documents use', function (): void {
    config(['plate.privacy_policy_version' => '2027-02-14']);

    $this->get(route('privacy.locale', ['locale' => 'mn']))
        ->assertOk()
        ->assertSee('Хүчин төгөлдөр болсон огноо: 2027 оны 2 дугаар сарын 14-ний өдөр', false);
});

it('sends people who use the app in French or Mongolian to their language unless they ask for English', function (string $route, string $locale): void {
    $this->actingAs(User::factory()->create(['locale' => $locale]));

    $this->get(route($route))->assertRedirect(route("{$route}.locale", ['locale' => $locale]));

    $this->get(route($route, ['lang' => 'en']))
        ->assertOk()
        ->assertSee('<html lang="en"', false);
})->with(['privacy', 'terms'])->with(['fr', 'mn']);
