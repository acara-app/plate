<?php

declare(strict_types=1);

use App\Ai\BaseAgent;
use App\Enums\ModelName;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Tool;

beforeEach(function (): void {
    $this->agent = new class extends BaseAgent
    {
        public function modelName(): ModelName
        {
            return ModelName::GEMINI_2_5_FLASH;
        }

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

it('returns default model name as Gemini 3 Flash', function (): void {
    $agent = new class extends BaseAgent
    {
        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };

    expect($agent->modelName())->toBe(ModelName::GEMINI_3_FLASH);
});

it('returns correct temperature for Gemini 3 Flash (1.0)', function (): void {
    $agent = new class extends BaseAgent
    {
        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };

    expect($agent->temperature())->toBe(1.0);
});

it('returns correct max tokens for Gemini 3 Flash (16384)', function (): void {
    $agent = new class extends BaseAgent
    {
        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };

    expect($agent->maxTokens())->toBe(16384);
});

it('returns thinking budget in provider options for Gemini 3 Flash', function (): void {
    $agent = new class extends BaseAgent
    {
        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };

    $providerOptions = $agent->providerOptions();

    expect($providerOptions)->toHaveKey('thinkingBudget')
        ->and($providerOptions['thinkingBudget'])->toBe(8192);
});

it('includes thought signature when tools are provided for thinking models', function (): void {
    $tool = (new Tool)
        ->as('test_tool')
        ->for('A test tool')
        ->using(fn (): string => 'result');

    $agent = new class($tool) extends BaseAgent
    {
        public function __construct(private readonly Tool $tool) {}

        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }

        public function tools(): array
        {
            return [$this->tool];
        }
    };

    $providerOptions = $agent->providerOptions();

    expect($providerOptions)->toHaveKey('thoughtSignature')
        ->and($providerOptions['thoughtSignature'])->toBeTrue();
});

it('does not include provider options for non-thinking models', function (): void {
    $agent = new class extends BaseAgent
    {
        public function modelName(): ModelName
        {
            return ModelName::GEMINI_2_5_FLASH;
        }

        public function systemPrompt(): string
        {
            return 'You are a test assistant.';
        }
    };

    $providerOptions = $agent->providerOptions();

    expect($providerOptions)->toBe([]);
});
