<?php

declare(strict_types=1);

namespace App\Services;

use stdClass;

final class VisualCatalog
{
    public const string NAME = 'acara-chat';

    private const string GENERATED_PATH = 'js/components/chat/visuals/generated/';

    private ?stdClass $catalog = null;

    public function prompt(): string
    {
        return mb_trim((string) file_get_contents(resource_path(self::GENERATED_PATH.self::NAME.'.prompt.md')));
    }

    public function version(): int
    {
        $version = $this->catalog()->version ?? null;

        return is_int($version) ? $version : 1;
    }

    /**
     * @return array{bytes: int, elements: int, depth: int, pointsPerSeries: int, visualsPerReply: int, patchesPerVisual: int}
     */
    public function limits(): array
    {
        $limits = $this->catalog()->limits ?? new stdClass;

        return [
            'bytes' => $this->limit($limits, 'bytes'),
            'elements' => $this->limit($limits, 'elements'),
            'depth' => $this->limit($limits, 'depth'),
            'pointsPerSeries' => $this->limit($limits, 'pointsPerSeries'),
            'visualsPerReply' => $this->limit($limits, 'visualsPerReply'),
            'patchesPerVisual' => $this->limit($limits, 'patchesPerVisual'),
        ];
    }

    /**
     * @return array{acceptsChildren: bool, props: stdClass}|null
     */
    public function component(string $type, int $version): ?array
    {
        $component = $this->entry('components', $type, $version);
        $props = $component->props ?? null;

        if (! $props instanceof stdClass) {
            return null;
        }

        return [
            'acceptsChildren' => ($component->acceptsChildren ?? false) === true,
            'props' => $props,
        ];
    }

    public function actionParams(string $name, int $version): ?stdClass
    {
        $params = $this->entry('actions', $name, $version)->params ?? null;

        return $params instanceof stdClass ? $params : null;
    }

    private function entry(string $group, string $name, int $version): ?stdClass
    {
        $entries = $this->catalog()->{$group} ?? null;
        $entry = $entries instanceof stdClass ? $entries->{$name} ?? null : null;
        $introducedIn = $entry instanceof stdClass ? $entry->introducedIn ?? null : null;

        return is_int($introducedIn) && $introducedIn <= $version ? $entry : null;
    }

    private function catalog(): stdClass
    {
        if ($this->catalog instanceof stdClass) {
            return $this->catalog;
        }

        $catalog = json_decode(
            (string) file_get_contents(resource_path(self::GENERATED_PATH.self::NAME.'.json')),
            flags: JSON_THROW_ON_ERROR,
        );

        return $this->catalog = $catalog instanceof stdClass ? $catalog : new stdClass;
    }

    private function limit(mixed $limits, string $key): int
    {
        $value = $limits instanceof stdClass ? $limits->{$key} ?? null : null;

        return is_int($value) ? $value : 0;
    }
}
