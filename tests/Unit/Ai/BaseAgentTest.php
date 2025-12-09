<?php

declare(strict_types=1);

use App\Ai\BaseAgent;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Tool;

beforeEach(function (): void {
    $this->agent = new class extends BaseAgent
    {
        public function provider(): Provider
        {
            return Provider::Gemini;
        }

        public function model(): string
        {
            return 'gemini-2.5-flash';
        }

        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };
});

it('returns empty tools array by default', function (): void {
    expect($this->agent->tools())->toBe([]);
});

it('returns default max tokens', function (): void {
    expect($this->agent->maxTokens())->toBe(8000);
});

it('returns empty client options by default', function (): void {
    expect($this->agent->clientOptions())->toBe([]);
});

it('creates text request with provider and model', function (): void {
    Prism::fake();

    $request = $this->agent->text();

    expect($request)->toBeInstanceOf(PendingRequest::class);
});

it('applies tools when provided', function (): void {
    Prism::fake();

    $tool = (new Tool)
        ->as('test_tool')
        ->for('A test tool')
        ->using(fn (): string => 'result');

    $agent = new class($tool) extends BaseAgent
    {
        public function __construct(private readonly Tool $tool) {}

        public function provider(): Provider
        {
            return Provider::Gemini;
        }

        public function model(): string
        {
            return 'gemini-2.5-flash';
        }

        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }

        public function tools(): array
        {
            return [$this->tool];
        }
    };

    $request = $agent->text();

    expect($request)->toBeInstanceOf(PendingRequest::class);
});

it('applies client options when provided', function (): void {
    Prism::fake();

    $agent = new class extends BaseAgent
    {
        public function provider(): Provider
        {
            return Provider::Gemini;
        }

        public function model(): string
        {
            return 'gemini-2.5-flash';
        }

        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }

        public function clientOptions(): array
        {
            return ['timeout' => 120];
        }
    };

    $request = $agent->text();

    expect($request)->toBeInstanceOf(PendingRequest::class);
});
