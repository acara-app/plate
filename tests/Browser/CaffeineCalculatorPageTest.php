<?php

declare(strict_types=1);

it('shows human-friendly copy on the caffeine calculator page', function (): void {
    visit('/tools/caffeine-calculator')
        ->assertSee('Find your personal caffeine limit')
        ->assertSee('Answer a few quick questions to get a daily limit in milligrams')
        ->assertSee('How caffeine usually feels for you')
        ->assertSee('Anything that changes your caffeine tolerance?')
        ->assertSee('Get my daily limit');
});
