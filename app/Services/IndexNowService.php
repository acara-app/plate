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

    private string $key;

    private ?string $keyLocation;

    public function __construct()
    {
        $this->host = config('services.indexnow.host', parse_url((string) config('app.url'), PHP_URL_HOST));
        $this->key = (string) config('services.indexnow.key');
        $this->keyLocation = config('services.indexnow.key_location');
    }

    /**
     * Submit URLs to IndexNow
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): bool
    {
        if ($this->key === '' || $this->key === '0') {
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
