<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Services\IndexNowServiceContract;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class SubmitSitemapsToIndexNowCommand extends Command
{
    protected $signature = 'sitemap:indexnow {--file=* : Specific sitemap files to process (relative to public path)}';

    protected $description = 'Submit URLs from sitemaps to IndexNow';

    public function handle(IndexNowServiceContract $indexNowService): int
    {
        $this->info('Starting IndexNow submission...');

        $files = $this->option('file');
        if (empty($files)) {
            $files = ['sitemap.xml', 'food_sitemap.xml'];
        }

        $allUrls = [];

        foreach ($files as $file) {
            if (! is_string($file)) {
                continue;
            }
            if ($file === '') {
                continue;
            }
            $path = public_path($file);

            if (! File::exists($path)) {
                $this->warn("Sitemap file not found: {$file}");

                continue;
            }

            $this->info("Processing {$file}...");
            $urls = $this->extractUrlsFromSitemap($path);
            $this->info('Found '.count($urls)." URLs in {$file}.");

            $allUrls = array_merge($allUrls, $urls);
        }

        $allUrls = array_unique($allUrls);

        if ($allUrls === []) {
            $this->warn('No URLs found to submit.');

            return self::SUCCESS;
        }

        $this->info('Submitting '.count($allUrls).' unique URLs to IndexNow...');

        $result = $indexNowService->submit($allUrls);

        if ($result->success) {
            $this->info("✓ {$result->message}");

            return self::SUCCESS;
        }

        $this->error($result->message);

        foreach ($result->errors as $error) {
            $this->error("  - {$error}");
        }

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function extractUrlsFromSitemap(string $path): array
    {
        $urls = [];
        try {
            $xml = simplexml_load_file($path);
            if ($xml === false) {
                return [];
            }

            $namespaces = $xml->getNamespaces(true);

            if (isset($namespaces[''])) {
                $xml->registerXPathNamespace('s', $namespaces['']);
                $elements = $xml->xpath('//s:loc');
            } else {
                $elements = $xml->xpath('//loc');
            }

            if ($elements) {
                foreach ($elements as $element) {
                    $urls[] = (string) $element;
                }
            }
        } catch (Exception $e) {
            $this->error("Error parsing {$path}: ".$e->getMessage());
        }

        return $urls;
    }
}
