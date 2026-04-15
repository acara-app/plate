<?php

declare(strict_types=1);

use App\Actions\GetHealthSyncSupportContextAction;
use App\Ai\Tools\GetHealthSyncSupport;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetHealthSyncSupport::class);

it('has correct name and description', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    expect($tool->name())->toBe('get_health_sync_support')
        ->and($tool->description())->toContain('Health Sync');
});

it('validates schema structure', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));
    $schema = new TestJsonSchema;

    $result = $tool->schema($schema);

    expect($result)->toBeArray()
        ->toHaveKeys(['topic']);
});

it('returns full context when topic is all', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'all']);
    $result = json_decode($tool->handle($request), true);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['topic'])->toBe('all')
        ->and($result['context'])->toHaveKeys(['overview', 'setup', 'platform_support', 'privacy', 'troubleshooting', 'links']);
});

it('returns specific topic context with links', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'setup']);
    $result = json_decode($tool->handle($request), true);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['topic'])->toBe('setup')
        ->and($result['context'])->toHaveKeys(['setup', 'links']);
});

it('returns specific topic context for each valid topic', function (string $topic): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => $topic]);
    $result = json_decode($tool->handle($request), true);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['topic'])->toBe($topic)
        ->and($result['context'])->toHaveKey($topic)
        ->and($result['context'])->toHaveKey('links');
})->with(['overview', 'setup', 'platform_support', 'privacy', 'troubleshooting']);

it('defaults to all when topic is not provided', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request([]);
    $result = json_decode($tool->handle($request), true);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['topic'])->toBe('all');
});

it('falls back to overview for unknown topic', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'nonexistent_topic']);
    $result = json_decode($tool->handle($request), true);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['context'])->toHaveKey('nonexistent_topic')
        ->and($result['context'])->toHaveKey('links');
});

it('includes setup steps with pairing token details', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'setup']);
    $result = json_decode($tool->handle($request), true);

    $setup = $result['context']['setup'];

    expect($setup)->toHaveKeys(['steps', 'pairing_token'])
        ->and($setup['pairing_token'])->toHaveKeys(['length', 'expires_after'])
        ->and($setup['pairing_token']['length'])->toBe(8);
});

it('includes platform support details', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'platform_support']);
    $result = json_decode($tool->handle($request), true);

    $platforms = $result['context']['platform_support'];

    expect($platforms)->toHaveKeys(['ios', 'android'])
        ->and($platforms['ios']['supported'])->toBeTrue()
        ->and($platforms['android']['automatic_sync_supported'])->toBeFalse();
});

it('includes privacy details', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'privacy']);
    $result = json_decode($tool->handle($request), true);

    $privacy = $result['context']['privacy'];

    expect($privacy)->toHaveKeys(['apple_health_access', 'encryption', 'destination', 'apple_health_write_access']);
});

it('includes troubleshooting steps', function (): void {
    $tool = new GetHealthSyncSupport(resolve(GetHealthSyncSupportContextAction::class));

    $request = new Request(['topic' => 'troubleshooting']);
    $result = json_decode($tool->handle($request), true);

    $troubleshooting = $result['context']['troubleshooting'];

    expect($troubleshooting)->toHaveKeys(['token_expired', 'pairing_fails', 'no_data_showing', 'sync_fails', 'support']);
});
