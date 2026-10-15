<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Billing\PhotoModel;
use App\Services\Ai\ConfiguredAgentModels;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

use function Laravel\Ai\agent;

beforeEach(function (): void {
    Http::allowStrayRequests([
        'https://api.openai.com/*',
        'https://generativelanguage.googleapis.com/*',
    ]);
});

it('accepts an image and returns the production photo schema', function (string $model): void {
    $image = UploadedFile::fake()->image('blank.jpg', 64, 64);
    $profile = resolve(ConfiguredAgentModels::class)->forModel($model);

    $result = resolve(FoodPhotoAnalyzerAgent::class)->usingModel(PhotoModel::fromAgentModel($profile, 4096))->analyze(
        base64_encode(file_get_contents($image->getRealPath())), 'image/jpeg',
    );

    expect($result->analyzerVersion)->toBe($model.'/p'.FoodPhotoAnalyzerAgent::PROMPT_VERSION);
    expect($result->items)->toHaveCount(0);
})->with(['gpt-6.1-sol', 'gemini-3.8-flash'])
    ->skip(fn (): bool => getenv('RUN_LIVE_AI_TESTS') !== '1', 'Opt in with RUN_LIVE_AI_TESTS=1; these tests call paid provider APIs.')
    ->group('live-ai');

it('streams a tool call and its completed response', function (string $model): void {
    $profile = resolve(ConfiguredAgentModels::class)->forModel($model);
    $tool = new class implements Tool
    {
        public function name(): string
        {
            return 'compatibility_probe';
        }

        public function description(): string
        {
            return 'Return the compatibility marker.';
        }

        public function schema(JsonSchema $schema): array
        {
            return [];
        }

        public function handle(Request $request): string
        {
            return 'MODEL_COMPATIBILITY_OK';
        }
    };
    $probe = agent(instructions: 'Call compatibility_probe once, then reply with exactly its result.', tools: [$tool]);

    $stream = $probe->stream('Run the compatibility probe.', provider: $profile->provider, model: $profile->model, timeout: 60);
    $toolResults = 0;
    foreach ($stream as $event) {
        if ($event instanceof Laravel\Ai\Streaming\Events\ToolResult && ! $event->preliminary) {
            $toolResults++;
        }
    }

    expect($stream->text)->toContain('MODEL_COMPATIBILITY_OK');
    expect($toolResults)->toBe(1);
})->with(['gpt-6.1-sol', 'gemini-3.8-flash'])
    ->skip(fn (): bool => getenv('RUN_LIVE_AI_TESTS') !== '1', 'Opt in with RUN_LIVE_AI_TESTS=1; these tests call paid provider APIs.')
    ->group('live-ai');

it('returns a localized structured title', function (string $model, string $language, string $locale): void {
    $profile = resolve(ConfiguredAgentModels::class)->forModel($model);

    $title = resolve(App\Ai\Agents\ConversationTitleGeneratorAgent::class)->usingAgentModel($profile)->generate(
        'Help me plan meals for the coming week.', $language, $locale,
    );

    expect($title)->not->toBeEmpty();
    expect(preg_match($locale === 'mn' ? '/\p{Cyrillic}/u' : '/[a-zA-Z]/', $title))->toBe(1);
})->with([
    ['gpt-6.1-sol', 'English', 'en'], ['gpt-6.1-sol', 'Mongolian', 'mn'],
    ['gemini-3.8-flash', 'English', 'en'], ['gemini-3.8-flash', 'Mongolian', 'mn'],
])->skip(fn (): bool => getenv('RUN_LIVE_AI_TESTS') !== '1', 'Opt in with RUN_LIVE_AI_TESTS=1; these tests call paid provider APIs.')
    ->group('live-ai');
