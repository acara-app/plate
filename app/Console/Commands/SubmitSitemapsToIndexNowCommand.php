<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Services\IndexNowServiceContract;
use App\Models\Content;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class SubmitSitemapsToIndexNowCommand extends Command
{
    protected $signature = 'sitemap:indexnow {--file=* : Specific sitemap files to process (relative to public path)}';

    protected $description = 'Submit URLs from sitemaps to IndexNow';

    public function handle(IndexNowServiceContract $indexNowService): int
    {
        $files = $this->option('file');
        if (empty($files)) {
            $files = ['sitemap.xml'];
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
                $this->warn('Sitemap file not found: '.$file);

                continue;
            }

            $allUrls = array_merge($allUrls, $this->extractUrlsFromSitemap($path));
        }

        $allUrls = array_merge($allUrls, $this->getFoodUrls(), $this->getPostUrls());

        $allUrls = array_unique($allUrls);

        if ($allUrls === []) {
            $this->warn('No URLs found to submit.');

            return self::SUCCESS;
        }

        $result = $indexNowService->submit($allUrls);

        if ($result->success) {
            $this->info('✓ '.$result->message);

            return self::SUCCESS;
        }

        $this->error($result->message);

        foreach ($result->errors as $error) {
            $this->error('  - '.$error);
        }

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function getFoodUrls(): array
    {
        try {
            return Content::query()
                ->published()
                ->food()
                ->orderBy('slug')
                ->get()
                ->map(fn (Content $food): string => route('food.show', $food->slug))
                ->values()
                ->all();
            // @codeCoverageIgnoreStart
        } catch (Exception $exception) {
            $this->error('Error fetching food URLs: '.$exception->getMessage());

            return [];
        }

        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array<int, string>
     */
    private function getPostUrls(): array
    {
        try {
            return Content::query()
                ->published()
                ->post()
                ->orderBy('slug')
                ->get()
                ->map(fn (Content $post): string => $post->locale === 'en'
                    ? route('post.show', $post->slug)
                    : route('post.locale.show', ['locale' => $post->locale, 'slug' => $post->slug])
                )
                ->values()
                ->all();
            // @codeCoverageIgnoreStart
        } catch (Exception $exception) {
            $this->error('Error fetching post URLs: '.$exception->getMessage());

            return [];
        }

        // @codeCoverageIgnoreEnd
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
        } catch (Exception $exception) {
            $this->error(sprintf('Error parsing %s: ', $path).$exception->getMessage());
        }

        return $urls;
    }
}
