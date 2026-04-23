<?php

declare(strict_types=1);

use App\Ai\Attributes\AiToolSensitivity;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Enums\DataSensitivity;
use App\Services\Ai\ToolSensitivityReader;
use Laravel\Ai\Contracts\Tool;

covers(ToolSensitivityReader::class);

beforeEach(function (): void {
    $this->reader = new ToolSensitivityReader;
});

describe('forTool', function (): void {
    it('returns the declared sensitivity from the attribute', function (string $toolClass, DataSensitivity $expected): void {
        expect($this->reader->forTool($toolClass))->toBe($expected);
    })->with([
        'General tool' => [GetCalorieLevelGuideline::class, DataSensitivity::General],
        'Personal tool' => [GetHealthGoals::class, DataSensitivity::Personal],
        'Sensitive tool' => [GetUserProfile::class, DataSensitivity::Sensitive],
    ]);

    it('accepts an instance as well as a class-string', function (): void {
        expect($this->reader->forTool(new GetCalorieLevelGuideline))
            ->toBe(DataSensitivity::General);
    });

    it('defaults to Sensitive when the class has no attribute', function (): void {
        $unlabeled = new class
        {
            public string $name = 'stub';
        };

        expect($this->reader->forTool($unlabeled))->toBe(DataSensitivity::Sensitive);
    });
});

describe('maxSensitivity', function (): void {
    it('returns General for an empty list', function (): void {
        expect($this->reader->maxSensitivity([]))->toBe(DataSensitivity::General);
    });

    it('returns the max sensitivity across a list of class-strings', function (): void {
        expect($this->reader->maxSensitivity([
            GetCalorieLevelGuideline::class,
            GetHealthGoals::class,
        ]))->toBe(DataSensitivity::Personal);

        expect($this->reader->maxSensitivity([
            GetCalorieLevelGuideline::class,
            GetHealthGoals::class,
            GetUserProfile::class,
        ]))->toBe(DataSensitivity::Sensitive);
    });

    it('treats an unlabeled tool as Sensitive when mixed with lower-tier tools', function (): void {
        $unlabeled = new class
        {
            public string $name = 'stub';
        };

        expect($this->reader->maxSensitivity([
            GetCalorieLevelGuideline::class,
            $unlabeled,
        ]))->toBe(DataSensitivity::Sensitive);
    });
});

it('labels every tool in the project', function (): void {
    $files = glob(app_path('Ai/Tools/*.php')) ?: [];

    /** @var list<string> $classes */
    $classes = array_map(
        fn (string $path): string => 'App\\Ai\\Tools\\'.basename($path, '.php'),
        $files,
    );

    expect($classes)->not->toBeEmpty();

    foreach ($classes as $class) {
        $reflection = new ReflectionClass($class);

        if (! $reflection->implementsInterface(Tool::class)) {
            continue;
        }

        $attributes = $reflection->getAttributes(AiToolSensitivity::class);

        expect($attributes)->not->toBeEmpty(
            sprintf('Tool %s must declare an #[AiToolSensitivity(...)] attribute.', $class),
        );
    }
});
