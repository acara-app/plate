<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\IndexNowServiceContract;
use App\DataObjects\IndexNowResultData;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class IndexNowService implements IndexNowServiceContract
{
    private const int TIMEOUT_SECONDS = 30;

    private const int MAX_URLS_PER_REQUEST = 10000;

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
    public function submit(array $urls): IndexNowResultData
    {
        if ($this->key === null || $this->key === '') {
            return IndexNowResultData::noKey();
        }

        if ($urls === []) {

            return IndexNowResultData::noUrls();
        }

        $chunks = array_chunk($urls, self::MAX_URLS_PER_REQUEST);
        $errors = [];
        $totalSubmitted = 0;

        foreach ($chunks as $index => $chunk) {
            $payload = [
                'host' => $this->host,
                'key' => $this->key,
                'urlList' => $chunk,
            ];

            if ($this->keyLocation) {
                $payload['keyLocation'] = $this->keyLocation;
            }

            try {
                /**
                 * @var \Illuminate\Http\Client\Response $response
                 */
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->post('https://api.indexnow.org/IndexNow', $payload);

                if ($response->successful()) {
                    $totalSubmitted += count($chunk);
                } else {
                    $errorMessage = 'Chunk '.($index + 1).": HTTP {$response->status()} - {$response->body()}";
                    $errors[] = $errorMessage;
                    Log::error("IndexNow: {$errorMessage}");
                }
            } catch (ConnectionException $e) {
                $errorMessage = 'Chunk '.($index + 1).': Connection timeout - the request took too long to complete.';
                $errors[] = $errorMessage;
                Log::error("IndexNow: Connection timeout during submission: {$e->getMessage()}");
            } catch (Exception $e) {
                $errorMessage = 'Chunk '.($index + 1).": {$e->getMessage()}";
                $errors[] = $errorMessage;
                Log::error("IndexNow: Exception during submission: {$e->getMessage()}");
            }
        }

        if ($errors === []) {
            return IndexNowResultData::success($totalSubmitted);
        }

        if ($totalSubmitted > 0) {
            return IndexNowResultData::failure(
                "Partially submitted {$totalSubmitted} URLs with some errors.",
                $errors,
                $totalSubmitted
            );
        }

        return IndexNowResultData::failure(
            'Failed to submit URLs to IndexNow.',
            $errors
        );
    }
}
