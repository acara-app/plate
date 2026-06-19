<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class MobileCapabilitiesData extends Data
{
    /**
     * @param  list<string>  $methods
     */
    public function __construct(
        public array $methods,
        public bool $chatFirst,
        public string $minAppVersion,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            methods: array_keys(array_filter(config()->array('mobile.auth_methods'))),
            chatFirst: config()->boolean('mobile.chat_first_enabled'),
            minAppVersion: config()->string('mobile.min_app_version'),
        );
    }
}
