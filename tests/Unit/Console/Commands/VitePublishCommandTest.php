<?php

declare(strict_types=1);

use App\Console\Commands\VitePublishCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('publishes vite assets to cdn', function (): void {
    Storage::fake('cdn');
    File::shouldReceive('allFiles')
        ->with(public_path('/build'))
        ->once()
        ->andReturn([]);

    $this->artisan(VitePublishCommand::class)
        ->expectsOutput('Publishing assets to CDN')
        ->expectsOutput('Published asset into build directory')
        ->expectsOutput('Vite assets published successfully!')
        ->assertSuccessful();
});

it('deletes existing build directory before publishing', function (): void {
    Storage::fake('cdn');
    Storage::disk('cdn')->put('build/test.js', 'content');

    File::shouldReceive('allFiles')
        ->with(public_path('/build'))
        ->once()
        ->andReturn([]);

    $this->artisan(VitePublishCommand::class)
        ->assertSuccessful();

    expect(Storage::disk('cdn')->exists('build/test.js'))->toBeFalse();
});

it('publishes files with correct mime types', function (): void {
    Storage::fake('cdn');

    $mockFile = Mockery::mock(Symfony\Component\Finder\SplFileInfo::class);
    $mockFile->shouldReceive('getRelativePathname')->andReturn('app.js');
    $mockFile->shouldReceive('getContents')->andReturn('console.log("test")');

    File::shouldReceive('allFiles')
        ->with(public_path('/build'))
        ->once()
        ->andReturn([$mockFile]);

    $this->artisan(VitePublishCommand::class)
        ->assertSuccessful();

    expect(Storage::disk('cdn')->exists('build/app.js'))->toBeTrue();
});
