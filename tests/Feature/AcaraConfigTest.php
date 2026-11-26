<?php

declare(strict_types=1);

it('loads the application defaults from the Acara configuration', function (string $key, string|int $expected): void {
    expect(config($key))->toBe($expected);
})->with([
    'Health Sync download' => ['acara.health_sync.app_store_url', 'https://apps.apple.com/us/app/acara-health-sync/id6761504525'],
    'Altani conversation history' => ['acara.altani.context.history_limit', 50],
    'Snap trial scans' => ['acara.snap.free_trial_scans', 2],
]);
