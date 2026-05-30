<?php

declare(strict_types=1);

it('features nutrition analyzer launch in the ai nutritionist blog section', function (): void {
    $this->get(route('ai-nutritionist'))
        ->assertOk()
        ->assertSee('Go deeper on meal data and nutrition quality')
        ->assertSee('Introducing the Nutrition Analyzer: Evidence-Based Nutrient Adequacy and Food-First Coaching')
        ->assertSee(route('post.show', 'nutrition-analyzer-launch'))
        ->assertSee('https://pub-plate-assets.acara.app/blog/nutrition-analyzer.webp')
        ->assertSee('Introducing the Acara Weight-Loss Analyzer')
        ->assertSee(route('post.show', 'weightloss-analyzer-launch'));
});
