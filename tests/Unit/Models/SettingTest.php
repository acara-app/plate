<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can set and get a setting using enum', function (): void {
    Setting::set(SettingKey::GeminiFileSearchStoreName, 'test-store-name');

    expect(Setting::get(SettingKey::GeminiFileSearchStoreName))->toBe('test-store-name');
});

it('can set and get a setting using string key', function (): void {
    Setting::set('custom_key', 'custom_value');

    expect(Setting::get('custom_key'))->toBe('custom_value');
});

it('returns default value when setting does not exist', function (): void {
    expect(Setting::get(SettingKey::GeminiFileSearchStoreName, 'default'))->toBe('default');
    expect(Setting::get('nonexistent', 'fallback'))->toBe('fallback');
});

it('updates existing setting when set is called again', function (): void {
    Setting::set(SettingKey::GeminiFileSearchStoreName, 'first-value');
    Setting::set(SettingKey::GeminiFileSearchStoreName, 'updated-value');

    expect(Setting::get(SettingKey::GeminiFileSearchStoreName))->toBe('updated-value');
    expect(Setting::query()->count())->toBe(1);
});
