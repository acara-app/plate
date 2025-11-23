<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DataObjects\GeminiFileSearchStoreData;
use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * This command is only temporarily needed to upload the FoodData Central JSON file
 * TODO: Refactor or find a better way to handle initial document uploads
 */
final class CheckGeminiFileSearchStoreCommand extends Command
{
    protected $signature = 'check:gemini-file-search-store';

    protected $description = 'Check Gemini File Search store information';

    public function handle(): void
    {
        $storeName = Setting::get(SettingKey::GeminiFileSearchStoreName);

        if (! $storeName) {
            $this->warn('No File Search store found in settings.');
            $this->info('Run "php artisan upload:document-to-gemini-file-search" first.');

            return;
        }

        if (! is_string($storeName)) {
            $this->error('Invalid store name in settings.');

            return;
        }

        $apiKey = config('gemini.api_key');
        /** @var string $baseUrl */
        $baseUrl = config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $this->info("Checking File Search store: {$storeName}");

        $storeResponse = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->get("{$baseUrl}/{$storeName}");

        if ($storeResponse->failed()) {
            $this->error("Failed to retrieve File Search store: {$storeResponse->body()}");

            return;
        }

        /** @var array<string, mixed> $data */
        $data = $storeResponse->json();
        $storeData = GeminiFileSearchStoreData::from($data);

        $this->info('File Search Store Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $storeData->name],
                ['Display Name', $storeData->displayName],
                ['Active Documents', $storeData->activeDocumentsCount],
                ['Size (MB)', $storeData->getSizeMB()],
                ['Create Time', $storeData->createTime],
                ['Update Time', $storeData->updateTime],
            ]
        );
    }
}
