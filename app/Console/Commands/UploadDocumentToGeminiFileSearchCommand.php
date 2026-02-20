<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Sleep;
use App\DataObjects\GeminiFileSearchStoreData;
use App\DataObjects\GeminiUploadedFileData;
use App\Enums\SettingKey;
use App\Models\Setting;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * This command is only temporarily needed to upload the FoodData Central JSON file
 * TODO: Refactor or find a better way to handle initial document uploads
 */
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
            $this->error('File not found: ' . $filePath);

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
        $baseUrl = config('gemini.base_url');

        $storeName = $this->getOrCreateStore($apiKey, $baseUrl);
        if (! $storeName) {
            return;
        }

        $this->info('Using File Search store: ' . $storeName);

        $storeData = $this->checkStoreStatus($apiKey, $baseUrl, $storeName);
        if ($storeData && $storeData->hasDocuments()) {
            $sizeMB = $storeData->getSizeMB();
            $this->info(sprintf('Store already contains documents: %d active, %d pending (%s MB)', $storeData->activeDocumentsCount, $storeData->pendingDocumentsCount, $sizeMB));
            $this->info('Skipping import.');

            return;
        }

        if ($storeData && $storeData->failedDocumentsCount > 0) {
            $this->warn(sprintf('Store has %d failed document(s). Proceeding with import...', $storeData->failedDocumentsCount));
        }

        if (! $this->importFile($apiKey, $baseUrl, $storeName, $file->name)) {
            return;
        }

        $this->verifyImport($apiKey, $baseUrl, $storeName);
    }

    private function uploadFile(string $filePath): ?GeminiUploadedFileData
    {
        $displayName = $this->option('display-name') ?? 'FoodData Central Foundation Food';

        $this->info('Uploading file...');

        $apiKey = config('gemini.api_key');
        $uploadUrl = 'https://generativelanguage.googleapis.com/upload/v1beta/files';

        try {
            $metadata = json_encode(['file' => ['displayName' => $displayName]]);
            $fileContent = File::get($filePath);

            // @phpstan-ignore-next-line
            if (! is_string($fileContent)) {
                $this->error('Failed to read file contents.');

                return null;
            }

            $fileName = basename($filePath);

            /** @var string $fileContent */
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'X-Goog-Upload-Protocol' => 'multipart',
            ])
                ->attach('metadata', $metadata, 'metadata', ['Content-Type' => 'application/json']) // @phpstan-ignore-line
                ->attach('file', $fileContent, $fileName, ['Content-Type' => 'application/json'])
                ->post($uploadUrl);

            if ($response->failed()) {
                $this->error(sprintf('File upload failed: %d %s', $response->status(), $response->body()));

                return null;
            }

            $data = $response->json('file');

            if (! is_array($data)) {
                $this->error('Invalid response format from file upload.');

                return null;
            }

            $name = $data['name'] ?? null;
            $displayNameFromApi = $data['displayName'] ?? null;
            $mimeType = $data['mimeType'] ?? null;
            $sizeBytes = $data['sizeBytes'] ?? null;
            $uri = $data['uri'] ?? null;

            if (! is_string($name) || ! is_string($uri)) {
                $this->error('Missing required fields in API response.');

                return null;
            }

            $this->info('File uploaded successfully.');

            return new GeminiUploadedFileData(
                name: $name,
                displayName: is_string($displayNameFromApi) ? $displayNameFromApi : $displayName,
                mimeType: is_string($mimeType) ? $mimeType : 'application/json',
                sizeBytes: is_int($sizeBytes) ? $sizeBytes : (is_string($sizeBytes) ? (int) $sizeBytes : mb_strlen($fileContent)),
                uri: $uri
            );
        } catch (Exception $exception) {
            $this->error('File upload failed: ' . $exception->getMessage());
            $this->error('Exception class: '.$exception::class);

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
        ])->post($baseUrl . '/fileSearchStores', [
            'displayName' => $storeDisplayName,
        ]);

        if ($response->failed()) {
            $this->error('Failed to create File Search store: ' . $response->body());

            return null;
        }

        $storeName = $response->json('name');
        if (! is_string($storeName)) {
            $this->error('Invalid store name in response.');

            return null;
        }

        Setting::set(SettingKey::GeminiFileSearchStoreName, $storeName);

        $this->info('File Search store created: ' . $storeName);

        return $storeName;
    }

    private function checkStoreStatus(string $apiKey, string $baseUrl, string $storeName): ?GeminiFileSearchStoreData
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->get(sprintf('%s/%s', $baseUrl, $storeName));

        if ($response->failed()) {
            $this->warn('Unable to check store status.');

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        // Ensure optional fields are present
        $data['activeDocumentsCount'] ??= 0;
        $data['pendingDocumentsCount'] ??= 0;
        $data['failedDocumentsCount'] ??= 0;
        $data['sizeBytes'] ??= 0;

        return GeminiFileSearchStoreData::from($data);
    }

    private function importFile(string $apiKey, string $baseUrl, string $storeName, string $fileName): bool
    {
        $this->info('Importing file into File Search store...');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->post(sprintf('%s/%s:importFile', $baseUrl, $storeName), [
            'file_name' => $fileName,
        ]);

        if ($response->failed()) {
            $this->error('Failed to import file: ' . $response->body());

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

        $attempts = 0;

        while (true) {
            $attempts++;
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->get(sprintf('%s/%s', $baseUrl, $operationName));

            if ($response->failed()) {
                $this->error('Failed to check operation status: ' . $response->body());

                return false;
            }

            $isDone = $response->json('done', false);

            if (! $isDone) {
                $this->info(sprintf('Operation still in progress (attempt %d)...', $attempts));
                /** @var int $pollingInterval */
                $pollingInterval = config('gemini.polling_interval', 10);
                Sleep::sleep($pollingInterval);

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
        $this->info(sprintf('✓ Verified: %d active, %d pending (%s MB)', $storeData->activeDocumentsCount, $storeData->pendingDocumentsCount, $sizeMB));
    }
}
