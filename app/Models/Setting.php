<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingKey;
use Carbon\CarbonInterface;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $key
 * @property string|null $value
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public static function get(SettingKey|string $key, mixed $default = null): mixed
    {
        $keyValue = $key instanceof SettingKey ? $key->value : $key;
        $setting = self::query()->where('key', $keyValue)->first();

        return $setting->value ?? $default;
    }

    public static function set(SettingKey|string $key, mixed $value): void
    {
        $keyValue = $key instanceof SettingKey ? $key->value : $key;

        self::query()->updateOrCreate(
            ['key' => $keyValue],
            ['value' => $value]
        );
    }
}
