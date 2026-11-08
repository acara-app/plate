<?php

declare(strict_types=1);

use App\Http\Controllers\ShowPrivacyPolicyController;
use App\Models\User;

covers(ShowPrivacyPolicyController::class);

beforeEach(function (): void {
    config(['plate.privacy_policy_version' => '2026-11-08']);
});

it('serves the same policy in English and French, each pointing to the other', function (): void {
    $english = $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('Effective Date: November 8, 2026')
        ->assertSee('hreflang="fr" href="'.route('privacy.locale', ['locale' => 'fr']).'"', false);

    $french = $this->get(route('privacy.locale', ['locale' => 'fr']))
        ->assertOk()
        ->assertSee('<html lang="fr"', false)
        ->assertSee("Date d'entrée en vigueur : 8 novembre 2026", false)
        ->assertSee('hreflang="en" href="'.route('privacy').'"', false);

    expect(mb_substr_count((string) $french->getContent(), '<h2>'))->toBe(mb_substr_count((string) $english->getContent(), '<h2>'));
});

it('sends people who use the app in French to the French policy unless they ask for English', function (): void {
    $this->actingAs(User::factory()->create(['locale' => 'fr']));

    $this->get(route('privacy'))->assertRedirect(route('privacy.locale', ['locale' => 'fr']));

    $this->get(route('privacy', ['lang' => 'en']))
        ->assertOk()
        ->assertSee('<html lang="en"', false);
});
