<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\VisualCatalog;

final readonly class ResolveVisualCatalogVersion
{
    public const string OFF = 'off';

    public function __construct(private VisualCatalog $catalog) {}

    public function handle(?int $announcedVersion): ?int
    {
        if ($announcedVersion === null || Setting::get(SettingKey::ChatVisuals) === self::OFF) {
            return null;
        }

        return min($announcedVersion, $this->catalog->version());
    }
}
