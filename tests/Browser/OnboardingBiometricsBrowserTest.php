<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;

it('asks whether a weight typed into the kilograms field was meant as pounds', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'age' => 30,
        'date_of_birth' => null,
        'height' => null,
        'weight' => null,
        'sex' => Sex::Male,
    ]);
    $this->actingAs($user);

    visit(route('onboarding.biometrics.show', absolute: false))->withLocale('en-GB')
        ->assertSee('Step 1 of 3')
        ->assertSee('33%')
        ->assertSee('Height (cm)')
        ->fill('#height', '175')
        ->fill('#weight', '193')
        ->press('Continue')
        ->assertSee('Did you mean 193 lb?')
        ->click('button:has-text("Yes, 193 lb")')
        ->assertSee('Weight (lb)')
        ->assertValue('#height_feet', '5')
        ->assertValue('#height_inches', '8.9')
        ->press('Continue')
        ->assertPathIs(route('onboarding.identity.show', absolute: false))
        ->assertNoJavaScriptErrors();

    expect($user->profile()->first())
        ->weight->toBe(87.54)
        ->height->toBe(175.01);
});

it('previews the equivalent measurements while typing and clears previews with empty or invalid inputs', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('onboarding.biometrics.show', absolute: false))->withLocale('en-GB')
        ->assertMissing('#weight-conversion-preview')
        ->assertMissing('#height-conversion-preview')
        ->fill('#height', '175')
        ->fill('#weight', '70')
        ->assertSeeIn('#height-conversion-preview', 'Approximately 5 ft 8.9 in')
        ->assertSeeIn('#weight-conversion-preview', 'Approximately 154.3 lb')
        ->fill('#weight', '')
        ->assertMissing('#weight-conversion-preview')
        ->fill('#height', '-1')
        ->assertMissing('#height-conversion-preview')
        ->fill('#height', '175')
        ->fill('#weight', '70')
        ->click('button:has-text("lb, ft, in")')
        ->fill('#height_feet', '5')
        ->fill('#height_inches', '10')
        ->fill('#weight', '200')
        ->assertSeeIn('#height-conversion-preview', 'Approximately 177.8 cm')
        ->assertSeeIn('#weight-conversion-preview', 'Approximately 90.72 kg')
        ->fill('#height_inches', '')
        ->assertSeeIn('#height-conversion-preview', 'Approximately 152.4 cm')
        ->fill('#height_feet', '')
        ->assertMissing('#height-conversion-preview')
        ->fill('#weight', '-1')
        ->assertMissing('#weight-conversion-preview')
        ->assertNoJavaScriptErrors();
});

it('lets returning users correct their measurements through readable mobile prompts without repeating onboarding', function (string $locale): void {
    $user = User::factory()->create(['locale' => $locale]);
    $profile = UserProfile::factory()->for($user)->create([
        'age' => 30,
        'date_of_birth' => null,
        'height' => 175,
        'weight' => 193,
        'sex' => Sex::Male,
        'needs_re_onboarding' => true,
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);
    $this->actingAs($user);

    $copy = fn (string $key): string => (string) __("common.onboarding.biometrics.{$key}", [], $locale);
    $usePoundsButton = sprintf('button:has-text(%s)', json_encode(str_replace('{{weight}}', '193', $copy('unit_check.use_pounds')), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    $findUnusablePromptControls = <<<'JS'
        (() => [...document.querySelectorAll('[data-slot="alert"]')].flatMap((alert) => {
            const bounds = alert.getBoundingClientRect();
            return [...alert.querySelectorAll('button')].filter((button) => {
                const rect = button.getBoundingClientRect();
                return rect.left < Math.max(0, bounds.left) - 1
                    || rect.right > Math.min(innerWidth, bounds.right) + 1
                    || rect.height < 44
                    || button.scrollWidth > button.clientWidth + 1;
            }).map((button) => button.textContent.trim());
        }))()
        JS;

    $page = visit(route('onboarding.biometrics.show', absolute: false))->withLocale($locale)
        ->resize(320, 568)
        ->assertSee($copy('review_heading'))
        ->assertSee($copy('review_save'))
        ->assertDontSee('33%')
        ->assertDontSee(str_replace(['{{current}}', '{{total}}'], ['1', '3'], $copy('step')));

    expect($page->script($findUnusablePromptControls))->toBe([]);

    $page->press('button[type="submit"]')
        ->assertSee(str_replace('{{weight}}', '193', $copy('unit_check.pounds_title')));

    expect($page->script($findUnusablePromptControls))->toBe([]);

    $page->click($usePoundsButton)
        ->assertSee($copy('weight_imperial'))
        ->assertValue('#weight', '193')
        ->fill('#weight', '70')
        ->press('button[type="submit"]')
        ->assertSee($copy('unit_check.measurements_title'));

    expect($page->script($findUnusablePromptControls))->toBe([]);

    $page->fill('#weight', '193')
        ->press('button[type="submit"]')
        ->assertPathIs(route('dashboard', absolute: false))
        ->assertMissing('[data-testid="biometrics-review-banner"]')
        ->assertNoJavaScriptErrors();

    expect($profile->refresh())
        ->weight->toBe(87.54)
        ->height->toBe(175.01)
        ->needs_re_onboarding->toBeFalse()
        ->onboarding_completed->toBeTrue();
})->with(['English' => 'en', 'French' => 'fr', 'Mongolian' => 'mn']);
