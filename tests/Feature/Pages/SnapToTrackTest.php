<?php

declare(strict_types=1);

use Livewire\Livewire;

it('renders the snap to track landing page', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertStatus(200)
        ->assertSee('Snap to Track')
        ->assertSee('Track calories & macros instantly with AI');
});

it('shows interactive demo trigger when page loads', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Try the interactive demo')
        ->assertSee('See how Snap to Track works');
});

it('shows tips for best results on initial state', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Tips for best results')
        ->assertSee('Take photo in good lighting')
        ->assertSee('Make sure all food is visible');
});

it('shows signup cta on initial state', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Sign up to start analyzing');
});

it('transitions to analyzing state when demo starts', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->assertSet('demoActive', true)
        ->assertSet('demoAnalyzing', true)
        ->assertSet('demoComplete', false)
        ->assertSee('Analyzing your meal...')
        ->assertSee('This is a demo with sample data');
});

it('shows demo results after analysis completes', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->call('showDemoResults')
        ->assertSet('demoComplete', true)
        ->assertSet('demoAnalyzing', false)
        ->assertSee('Interactive Demo')
        ->assertSee('416')
        ->assertSee('Grilled Chicken Breast')
        ->assertSee('Steamed Brown Rice')
        ->assertSee('Mixed Green Salad')
        ->assertSee('92% confident');
});

it('shows macro breakdown in demo results', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->call('showDemoResults')
        ->assertSee('37.6')
        ->assertSee('51.5')
        ->assertSee('5.8')
        ->assertSee('Protein')
        ->assertSee('Carbs')
        ->assertSee('Fat');
});

it('shows per-item details in demo results', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->call('showDemoResults')
        ->assertSee('Food Items Detected')
        ->assertSee('Grilled Chicken Breast')
        ->assertSee('~120g')
        ->assertSee('165')
        ->assertSee('Steamed Brown Rice')
        ->assertSee('~1 cup');
});

it('shows signup cta after demo results', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->call('showDemoResults')
        ->assertSee('Sign up to analyze your own meals')
        ->assertSee('Already have an account?')
        ->assertSee('Log in');
});

it('can reset demo to initial state', function (): void {
    Livewire::test('pages::snap-to-track')
        ->call('startDemo')
        ->call('showDemoResults')
        ->call('resetDemo')
        ->assertSet('demoActive', false)
        ->assertSet('demoAnalyzing', false)
        ->assertSet('demoComplete', false)
        ->assertSee('Try the interactive demo');
});

it('shows how it works section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('How it works')
        ->assertSee('Snap a photo of your meal')
        ->assertSee('AI identifies each food item')
        ->assertSee('Get instant macro breakdown');
});

it('shows disclaimer about AI estimates', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Disclaimer')
        ->assertSee('These are AI estimates');
});

it('shows faq section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Frequently Asked Questions')
        ->assertSee('How does the food photo analyzer work?')
        ->assertSee('How accurate are the calorie estimates?')
        ->assertSee('How do I use Snap to Track?');
});

it('shows main app promo section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Need more than just tracking?')
        ->assertSee('Get Started');
});

it('shows explore more tools section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Explore More Tools')
        ->assertSee('View All Tools');
});
