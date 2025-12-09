<?php

declare(strict_types=1);

use App\Ai\SystemPrompt;

it('generates prompt with only background', function () {
    $prompt = new SystemPrompt(
        background: [
            'You are an expert nutritionist.',
            'You help users plan meals.',
        ],
    );

    $result = (string) $prompt;

    expect($result)
        ->toContain('# IDENTITY AND PURPOSE')
        ->toContain('You are an expert nutritionist.')
        ->toContain('You help users plan meals.')
        ->not->toContain('# INTERNAL ASSISTANT STEPS')
        ->not->toContain('# OUTPUT INSTRUCTIONS')
        ->not->toContain('# TOOLS USAGE RULES');
});

it('generates prompt with all sections', function () {
    $prompt = new SystemPrompt(
        background: ['You are an AI assistant.'],
        steps: ['1. Analyze the request', '2. Generate response'],
        output: ['Use JSON format', 'Be concise'],
        toolsUsage: ['Use file_search for data', 'Verify before using'],
    );

    $result = (string) $prompt;

    expect($result)
        ->toContain('# IDENTITY AND PURPOSE')
        ->toContain('You are an AI assistant.')
        ->toContain('# INTERNAL ASSISTANT STEPS')
        ->toContain('1. Analyze the request')
        ->toContain('2. Generate response')
        ->toContain('# OUTPUT INSTRUCTIONS')
        ->toContain(' - Use JSON format')
        ->toContain(' - Be concise')
        ->toContain('# TOOLS USAGE RULES')
        ->toContain(' - Use file_search for data')
        ->toContain(' - Verify before using');
});

it('generates prompt with steps only', function () {
    $prompt = new SystemPrompt(
        background: ['Background info.'],
        steps: ['Step 1', 'Step 2'],
    );

    $result = (string) $prompt;

    expect($result)
        ->toContain('# IDENTITY AND PURPOSE')
        ->toContain('# INTERNAL ASSISTANT STEPS')
        ->toContain('Step 1')
        ->toContain('Step 2')
        ->not->toContain('# OUTPUT INSTRUCTIONS')
        ->not->toContain('# TOOLS USAGE RULES');
});

it('generates prompt with output instructions only', function () {
    $prompt = new SystemPrompt(
        background: ['Background info.'],
        output: ['Output rule 1', 'Output rule 2'],
    );

    $result = (string) $prompt;

    expect($result)
        ->toContain('# IDENTITY AND PURPOSE')
        ->toContain('# OUTPUT INSTRUCTIONS')
        ->toContain(' - Output rule 1')
        ->toContain(' - Output rule 2')
        ->not->toContain('# INTERNAL ASSISTANT STEPS')
        ->not->toContain('# TOOLS USAGE RULES');
});

it('generates prompt with tools usage only', function () {
    $prompt = new SystemPrompt(
        background: ['Background info.'],
        toolsUsage: ['Tool rule 1', 'Tool rule 2'],
    );

    $result = (string) $prompt;

    expect($result)
        ->toContain('# IDENTITY AND PURPOSE')
        ->toContain('# TOOLS USAGE RULES')
        ->toContain(' - Tool rule 1')
        ->toContain(' - Tool rule 2')
        ->not->toContain('# INTERNAL ASSISTANT STEPS')
        ->not->toContain('# OUTPUT INSTRUCTIONS');
});

it('implements Stringable interface', function () {
    $prompt = new SystemPrompt(background: ['Test background.']);

    expect($prompt)->toBeInstanceOf(Stringable::class);
});

it('joins background items with newlines', function () {
    $prompt = new SystemPrompt(
        background: ['Line 1', 'Line 2', 'Line 3'],
    );

    $result = (string) $prompt;

    expect($result)->toContain("Line 1\nLine 2\nLine 3");
});

it('prefixes output items with dash', function () {
    $prompt = new SystemPrompt(
        background: ['Background.'],
        output: ['First rule', 'Second rule'],
    );

    $result = (string) $prompt;

    expect($result)->toContain(" - First rule\n - Second rule");
});

it('prefixes tools usage items with dash', function () {
    $prompt = new SystemPrompt(
        background: ['Background.'],
        toolsUsage: ['First tool rule', 'Second tool rule'],
    );

    $result = (string) $prompt;

    expect($result)->toContain(" - First tool rule\n - Second tool rule");
});
