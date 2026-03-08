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
     * @return array<int, Tool>
     */
    public function getTools(): array
    {
        return $this->buildTools(config('plate.tools', []));
    }

    /**
     * @param  array<int, Base64Image>  $images
     * @return array<int, Tool>
     */
    public function getImageTools(array $images): array
    {
        return $this->buildTools(config('plate.image_tools', []), ['images' => $images]);
    }

    /**
     * @return array<int, Tool>
     */
    public function getMealPlanTools(): array
    {
        return $this->buildTools(config('plate.meal_plan_tools', []));
    }

    /**
     * @return array<int, ProviderTool>
     */
    public function getProviderTools(): array
    {
        return $this->buildTools(config('plate.provider_tools', []));
    }

    /**
     * @param  array<int, class-string>  $classes
     * @param  array<string, mixed>  $constructorArgs
     * @return array<int, Tool|ProviderTool>
     */
    private function buildTools(array $classes, array $constructorArgs = []): array
    {
        return collect($classes)
            ->map(fn (string $class) => $this->container->make($class, $constructorArgs))
            ->all();
    }
}
