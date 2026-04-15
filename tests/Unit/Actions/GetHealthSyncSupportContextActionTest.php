<?php

declare(strict_types=1);

use App\Actions\GetHealthSyncSupportContextAction;

covers(GetHealthSyncSupportContextAction::class);

it('returns full context when topic is all', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle('all');

    expect($result)->toHaveKeys(['overview', 'setup', 'platform_support', 'privacy', 'troubleshooting', 'links'])
        ->and($result['overview']['app_name'])->toBe('Acara Health Sync')
        ->and($result['links'])->toHaveKeys(['app_store_url', 'health_sync_url', 'setup_guide_url', 'mobile_sync_settings_url', 'install_app_url', 'support_email']);
});

it('returns full context by default', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle();

    expect($result)->toHaveKey('overview')
        ->and($result)->toHaveKey('setup')
        ->and($result)->toHaveKey('platform_support');
});

it('returns specific topic with links', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle('setup');

    expect($result)->toHaveKey('setup')
        ->and($result)->toHaveKey('links')
        ->and($result['setup'])->toHaveKey('steps')
        ->and($result['setup'])->toHaveKey('pairing_token');
});

it('falls back to overview for unknown topic', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle('nonexistent');

    expect($result)->toHaveKey('nonexistent')
        ->and($result['nonexistent'])->toHaveKey('app_name')
        ->and($result)->toHaveKey('links');
});

it('includes platform support details', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle('platform_support');

    expect($result['platform_support']['ios']['supported'])->toBeTrue()
        ->and($result['platform_support']['android']['automatic_sync_supported'])->toBeFalse();
});

it('includes troubleshooting guidance', function (): void {
    $action = resolve(GetHealthSyncSupportContextAction::class);
    $result = $action->handle('troubleshooting');

    expect($result['troubleshooting'])->toHaveKeys(['token_expired', 'pairing_fails', 'no_data_showing', 'sync_fails', 'support']);
});
