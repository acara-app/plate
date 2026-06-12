<?php

declare(strict_types=1);

it('renders the ai accuracy page', function (): void {
    $this->get(route('ai-accuracy'))
        ->assertSuccessful()
        ->assertSee('AI Accuracy & Limitations');
});

it('keeps unbacked quantitative accuracy claims off public pages', function (string $path): void {
    $content = $this->get($path)->assertSuccessful()->getContent();

    expect($content)
        ->not->toContain('10–20%')
        ->not->toContain('10-20%')
        ->and(preg_match('/within roughly \d+/', $content))->toBe(0);
})->with([
    'snap to track' => '/tools/snap-to-track',
    'ai accuracy' => '/ai-accuracy',
    'support' => '/support',
]);
