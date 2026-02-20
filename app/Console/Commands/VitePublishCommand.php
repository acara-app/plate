<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

final class VitePublishCommand extends Command
{
    protected $signature = 'vite:publish';

    protected $description = 'Publish the Vite build to configured CDN and invalidate CloudFront cache';

    public function handle(): void
    {
        $this->info('Publishing assets to CDN');

        Storage::disk('cdn')->deleteDirectory('build');

        $directories = ['build'];

        foreach ($directories as $dir) {
            $this->publishDirectory($dir);
        }

        $this->info('Vite assets published successfully!');
    }

    private function publishDirectory(string $directory): void
    {
        $files = File::allFiles(public_path('/'.$directory));

        foreach ($files as $asset) {
            $mime = getMimeType($asset->getRelativePathname());
            $path = $directory.'/'.$asset->getRelativePathname();

            Storage::disk('cdn')->put(
                $path,
                $asset->getContents(),
                ['ContentType' => $mime]
            );
        }

        $this->info(sprintf('Published asset into %s directory', $directory));
    }
}
