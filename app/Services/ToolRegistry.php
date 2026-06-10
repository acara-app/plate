<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Container\Container;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Providers\Tools\ProviderTool;

/** @codeCoverageIgnore */
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
        $classes = config()->array('plate.tools', []);

        return $this->resolve($classes);
    }

    /**
     * @param  array<int, Base64Image>  $images
     * @return array<int, Tool|ProviderTool>
     */
    public function getImageTools(array $images): array
    {
        /** @var array<int, class-string> $classes */
        $classes = config()->array('plate.image_tools', []);

        return $this->resolve($classes, ['images' => $images]);
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function getSharedTools(): array
    {
        /** @var array<int, class-string> $classes */
        $classes = config()->array('plate.shared_tools', []);

        return $this->resolve($classes);
    }

    /**
     * @return array<int, Agent>
     */
    public function getSubAgents(): array
    {
        /** @var array<int, class-string<Agent>> $classes */
        $classes = config()->array('plate.sub_agents', []);

        return collect($classes)
            ->map(function (string $class): Agent {
                /** @var Agent */
                return $this->container->make($class);
            })
            ->all();
    }

    /**
     * @return array<int, ProviderTool>
     */
    public function getProviderTools(): array
    {
        /** @var array<int, class-string<ProviderTool>> $classes */
        $classes = config()->array('plate.provider_tools', []);

        /** @var array<int, ProviderTool> */
        return $this->resolve($classes);
    }

    /**
     * @param  array<int, class-string>  $classes
     * @param  array<string, mixed>  $constructorArgs
     * @return array<int, Tool|ProviderTool>
     */
    public function resolve(array $classes, array $constructorArgs = []): array
    {
        return collect($classes)
            ->map(function (string $class) use ($constructorArgs): Tool|ProviderTool {
                /** @var Tool|ProviderTool */
                return $this->container->make($class, $constructorArgs);
            })
            ->all();
    }
}
