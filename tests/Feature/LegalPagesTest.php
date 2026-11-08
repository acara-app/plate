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

it('serves the same page in English and French, each pointing to the other', function (string $route, string $englishDate, string $frenchDate): void {
    $english = $this->get(route($route))
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee($englishDate)
        ->assertSee('hreflang="fr" href="'.route("{$route}.locale", ['locale' => 'fr']).'"', false);

    $french = $this->get(route("{$route}.locale", ['locale' => 'fr']))
        ->assertOk()
        ->assertSee('<html lang="fr"', false)
        ->assertSee($frenchDate, false)
        ->assertSee('hreflang="en" href="'.route($route).'"', false);

    expect(mb_substr_count((string) $french->getContent(), '<h2>'))->toBe(mb_substr_count((string) $english->getContent(), '<h2>'));
})->with([
    'privacy policy' => ['privacy', 'Effective Date: November 8, 2026', "Date d'entrée en vigueur : 8 novembre 2026"],
    'terms of service' => ['terms', 'Last Updated: November 8, 2026', 'Dernière mise à jour : 8 novembre 2026'],
]);

it('sends people who use the app in French to the French page unless they ask for English', function (string $route): void {
    $this->actingAs(User::factory()->create(['locale' => 'fr']));

    $this->get(route($route))->assertRedirect(route("{$route}.locale", ['locale' => 'fr']));

    $this->get(route($route, ['lang' => 'en']))
        ->assertOk()
        ->assertSee('<html lang="en"', false);
})->with(['privacy', 'terms']);
