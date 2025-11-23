<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DataObjects\GeminiFileSearchStoreData;
use App\DataObjects\GeminiUploadedFileData;
use App\Enums\SettingKey;
use App\Models\Setting;
use Exception;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class UploadDocumentToGeminiFileSearchCommand extends Command
{
    protected $signature = 'upload:document-to-gemini-file-search 
        {--file-path= : Path to the file to upload}
        {--display-name= : Display name for the uploaded file}
        {--store-name= : Display name for the file search store}';

    protected $description = 'Upload document to Gemini File Search';

    public function handle(): void
    {
        /** @var string $filePath */
        $filePath = $this->option('file-path')
            ?? config('gemini.default_upload_file_path', storage_path('sources/FoodData_Central_foundation_food_json_2025-04-24 3.json'));

        if (! File::exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return;
        }

        $file = $this->uploadFile($filePath);
        if (! $file instanceof GeminiUploadedFileData) {
            return;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey)) {
            $this->error('Invalid API key configuration.');

            return;
        }

        /** @var string $baseUrl */
        $baseUrl = config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $storeName = $this->getOrCreateStore($apiKey, $baseUrl);
        if (! $storeName) {
            return;
        }

        $this->info("Using File Search store: {$storeName}");

        $storeData = $this->checkStoreStatus($apiKey, $baseUrl, $storeName);
        if ($storeData && $storeData->hasDocuments()) {
            $sizeMB = $storeData->getSizeMB();
            $this->info("Store already contains documents: {$storeData->activeDocumentsCount} active, {$storeData->pendingDocumentsCount} pending ({$sizeMB} MB)");
            $this->info('Skipping import.');

            return;
        }

        if ($storeData && $storeData->failedDocumentsCount > 0) {
            $this->warn("Store has {$storeData->failedDocumentsCount} failed document(s). Proceeding with import...");
        }

        if (! $this->importFile($apiKey, $baseUrl, $storeName, $file->name)) {
            return;
        }

        $this->verifyImport($apiKey, $baseUrl, $storeName);
    }

    private function uploadFile(string $filePath): ?GeminiUploadedFileData
    {
        $displayName = $this->option('display-name') ?? 'FoodData Central Foundation Food';

        try {
            $file = Gemini::files()->upload(
                filename: $filePath,
                mimeType: MimeType::APPLICATION_JSON,
                displayName: $displayName
            );

            $this->info('File uploaded successfully.');

            return GeminiUploadedFileData::from($file);
        } catch (Exception $e) {
            $this->error("File upload failed: {$e->getMessage()}");

            return null;
        }
    }

    private function getOrCreateStore(string $apiKey, string $baseUrl): ?string
    {
        $storeName = Setting::get(SettingKey::GeminiFileSearchStoreName);

        if ($storeName && is_string($storeName)) {
            return $storeName;
        }

        $this->info('Creating File Search store...');

        $storeDisplayName = $this->option('store-name') ?? 'FoodData Central Store';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->post("{$baseUrl}/fileSearchStores", [
            'displayName' => $storeDisplayName,
        ]);

        if ($response->failed()) {
            $this->error("Failed to create File Search store: {$response->body()}");

            return null;
        }

        $storeName = $response->json('name');
        if (! is_string($storeName)) {
            $this->error('Invalid store name in response.');

            return null;
        }

        Setting::set(SettingKey::GeminiFileSearchStoreName, $storeName);

        $this->info("File Search store created: {$storeName}");

        return $storeName;
    }

    private function checkStoreStatus(string $apiKey, string $baseUrl, string $storeName): ?GeminiFileSearchStoreData
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->get("{$baseUrl}/{$storeName}");

        if ($response->failed()) {
            $this->warn('Unable to check store status.');

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return GeminiFileSearchStoreData::from($data);
    }

    private function importFile(string $apiKey, string $baseUrl, string $storeName, string $fileName): bool
    {
        $this->info('Importing file into File Search store...');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->post("{$baseUrl}/{$storeName}:importFile", [
            'file_name' => $fileName,
        ]);

        if ($response->failed()) {
            $this->error("Failed to import file: {$response->body()}");

            return false;
        }

        $operationName = $response->json('name');
        if (! is_string($operationName)) {
            $this->error('Invalid operation name in response.');

            return false;
        }

        return $this->waitForOperation($apiKey, $baseUrl, $operationName);
    }

    private function waitForOperation(string $apiKey, string $baseUrl, string $operationName): bool
    {
        $this->info('Waiting for import operation to complete...');

        /** @var int $maxAttempts */
        $maxAttempts = config('gemini.max_polling_attempts', 60);
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->get("{$baseUrl}/{$operationName}");

            if ($response->failed()) {
                $this->error("Failed to check operation status: {$response->body()}");

                return false;
            }

            $isDone = $response->json('done', false);

            if (! $isDone) {
                $this->info("Operation still in progress (attempt {$attempts}/{$maxAttempts})...");
                /** @var int $pollingInterval */
                $pollingInterval = config('gemini.polling_interval', 10);
                \Illuminate\Support\Sleep::sleep($pollingInterval);

                continue;
            }

            $error = $response->json('error');

            if ($error) {
                $this->error('Import operation failed: '.json_encode($error));

                return false;
            }

            $this->info('Import completed successfully!');

            return true;
        }

        $this->error('Operation timed out after maximum attempts.');

        return false;
    }

    private function verifyImport(string $apiKey, string $baseUrl, string $storeName): void
    {
        $this->newLine();
        $this->info('Verifying import...');

        $storeData = $this->checkStoreStatus($apiKey, $baseUrl, $storeName);

        if (! $storeData instanceof GeminiFileSearchStoreData) {
            return;
        }

        if ($storeData->activeDocumentsCount === 0 && $storeData->pendingDocumentsCount === 0) {
            $this->warn('⚠ Document count is still 0. This may take a few moments to update.');

            return;
        }

        $sizeMB = $storeData->getSizeMB();
        $this->info("✓ Verified: {$storeData->activeDocumentsCount} active, {$storeData->pendingDocumentsCount} pending ({$sizeMB} MB)");
    }
}
