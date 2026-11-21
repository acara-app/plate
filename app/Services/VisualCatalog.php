<?php

declare(strict_types=1);

namespace App\Services;

use stdClass;

final readonly class VisualCatalog
{
    private stdClass $catalog;

    public function __construct()
    {
        $catalog = json_decode(
            (string) file_get_contents(resource_path('js/components/chat/visuals/generated/acara-chat.json')),
            flags: JSON_THROW_ON_ERROR,
        );

        $this->catalog = $catalog instanceof stdClass ? $catalog : new stdClass;
    }

    public function version(): int
    {
        $version = $this->catalog->version ?? null;

        return is_int($version) ? $version : 1;
    }

    /**
     * @return array{bytes: int, elements: int, depth: int, pointsPerSeries: int, visualsPerReply: int, patchesPerVisual: int}
     */
    public function limits(): array
    {
        $limits = $this->catalog->limits ?? new stdClass;

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
        $entries = $this->catalog->{$group} ?? null;
        $entry = $entries instanceof stdClass ? $entries->{$name} ?? null : null;
        $introducedIn = $entry instanceof stdClass ? $entry->introducedIn ?? null : null;

        return is_int($introducedIn) && $introducedIn <= $version ? $entry : null;
    }

    private function limit(mixed $limits, string $key): int
    {
        $value = $limits instanceof stdClass ? $limits->{$key} ?? null : null;

        return is_int($value) ? $value : 0;
    }
}
