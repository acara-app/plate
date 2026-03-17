<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Container\Container;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Providers\Tools\ProviderTool;

final readonly class ToolRegistry
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function getTools(): array
    {
        /** @var array<int, class-string> $classes */
        $classes = config('plate.tools', []);

        return $this->buildTools($classes);
    }

    /**
     * @param  array<int, Base64Image>  $images
     * @return array<int, Tool|ProviderTool>
     */
    public function getImageTools(array $images): array
    {
        /** @var array<int, class-string> $classes */
        $classes = config('plate.image_tools', []);

        return $this->buildTools($classes, ['images' => $images]);
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function getMealPlanTools(): array
    {
        /** @var array<int, class-string> $classes */
        $classes = config('plate.meal_plan_tools', []);

        return $this->buildTools($classes);
    }

    /**
     * @return array<int, ProviderTool>
     */
    public function getProviderTools(): array
    {
        /** @var array<int, class-string<ProviderTool>> $classes */
        $classes = config('plate.provider_tools', []);

        /** @var array<int, ProviderTool> */
        return $this->buildTools($classes);
    }

    /**
     * @param  array<int, class-string>  $classes
     * @param  array<string, mixed>  $constructorArgs
     * @return array<int, Tool|ProviderTool>
     */
    private function buildTools(array $classes, array $constructorArgs = []): array
    {
        return collect($classes)
            ->map(function (string $class) use ($constructorArgs): Tool|ProviderTool {
                /** @var Tool|ProviderTool */
                return $this->container->make($class, $constructorArgs);
            })
            ->all();
    }
}
