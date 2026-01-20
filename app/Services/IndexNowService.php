<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\IndexNowServiceInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class IndexNowService implements IndexNowServiceInterface
{
    private string $host;

    private ?string $key;

    private ?string $keyLocation;

    public function __construct()
    {
        /** @var string|null $configHost */
        $configHost = config('services.indexnow.host');
        /** @var string $appUrl */
        $appUrl = config('app.url');
        $this->host = $configHost ?? (string) parse_url($appUrl, PHP_URL_HOST);
        /** @var string|null $key */
        $key = config('services.indexnow.key');
        $this->key = $key;
        /** @var string|null $keyLocation */
        $keyLocation = config('services.indexnow.key_location');
        $this->keyLocation = $keyLocation;
    }

    /**
     * Submit URLs to IndexNow
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): bool
    {
        if ($this->key === null || $this->key === '') {
            Log::warning('IndexNow: No key configured. Skipping submission.');

            return false;
        }

        if ($urls === []) {
            Log::info('IndexNow: No URLs to submit.');

            return true;
        }

        // IndexNow allows maximum 10,000 URLs per request
        $chunks = array_chunk($urls, 10000);
        $allSuccessful = true;

        foreach ($chunks as $chunk) {
            $payload = [
                'host' => $this->host,
                'key' => $this->key,
                'urlList' => $chunk,
            ];

            if ($this->keyLocation) {
                $payload['keyLocation'] = $this->keyLocation;
            }

            try {
                $response = Http::post('https://api.indexnow.org/IndexNow', $payload);

                if ($response->successful()) {
                    Log::info('IndexNow: Successfully submitted '.count($chunk).' URLs.');
                } else {
                    Log::error("IndexNow: Failed to submit URLs. Status: {$response->status()}, Body: {$response->body()}");
                    $allSuccessful = false;
                }
            } catch (Exception $e) {
                Log::error("IndexNow: Exception during submission: {$e->getMessage()}");
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
    }
}
