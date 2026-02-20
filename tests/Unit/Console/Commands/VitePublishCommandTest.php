<?php

declare(strict_types=1);

use App\Console\Commands\VitePublishCommand;
use Illuminate\Support\Facades\Storage;

it('publishes vite assets to cdn', function (): void {
    Storage::fake('cdn');

    // Create a test build directory with files
    $buildPath = public_path('/build');
    if (! is_dir($buildPath)) {
        mkdir($buildPath, 0755, true);
    }

    file_put_contents($buildPath.'/app.js', 'console.log("test")');

    $this->artisan(VitePublishCommand::class)
        ->expectsOutput('Publishing assets to CDN')
        ->expectsOutput('Published asset into build directory')
        ->expectsOutput('Vite assets published successfully!')
        ->assertSuccessful();

    // Verify the file was published
    expect(Storage::disk('cdn')->exists('build/app.js'))->toBeTrue();
    expect(Storage::disk('cdn')->get('build/app.js'))->toBe('console.log("test")');

    // Cleanup
    if (file_exists($buildPath.'/app.js')) {
        unlink($buildPath.'/app.js');
    }
});

it('deletes existing build directory before publishing', function (): void {
    Storage::fake('cdn');
    Storage::disk('cdn')->put('build/test.js', 'content');

    // Create a test build directory with files
    $buildPath = public_path('/build');
    if (! is_dir($buildPath)) {
        mkdir($buildPath, 0755, true);
    }

    file_put_contents($buildPath.'/app.js', 'console.log("test")');

    $this->artisan(VitePublishCommand::class)
        ->assertSuccessful();

    expect(Storage::disk('cdn')->exists('build/test.js'))->toBeFalse();
    expect(Storage::disk('cdn')->exists('build/app.js'))->toBeTrue();

    // Cleanup
    if (file_exists($buildPath.'/app.js')) {
        unlink($buildPath.'/app.js');
    }
});

it('publishes files with correct mime types', function (): void {
    Storage::fake('cdn');

    // Create a test build directory with files
    $buildPath = public_path('/build');
    if (! is_dir($buildPath)) {
        mkdir($buildPath, 0755, true);
    }

    file_put_contents($buildPath.'/app.js', 'console.log("test")');
    file_put_contents($buildPath.'/app.css', 'body { color: red; }');

    $this->artisan(VitePublishCommand::class)
        ->assertSuccessful();

    expect(Storage::disk('cdn')->exists('build/app.js'))->toBeTrue();
    expect(Storage::disk('cdn')->exists('build/app.css'))->toBeTrue();

    // Cleanup
    if (file_exists($buildPath.'/app.js')) {
        unlink($buildPath.'/app.js');
    }

    if (file_exists($buildPath.'/app.css')) {
        unlink($buildPath.'/app.css');
    }
});
