<?php

declare(strict_types=1);

use App\DataObjects\GeminiFileSearchStoreData;

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'fileSearchStores/test-store',
        'displayName' => 'Test Store',
        'activeDocumentsCount' => '5',
        'pendingDocumentsCount' => '2',
        'failedDocumentsCount' => '0',
        'sizeBytes' => '1048576',
        'createTime' => '2025-11-23T10:00:00Z',
        'updateTime' => '2025-11-23T11:00:00Z',
    ];

    $storeData = GeminiFileSearchStoreData::from($data);

    expect($storeData)
        ->name->toBe('fileSearchStores/test-store')
        ->displayName->toBe('Test Store')
        ->activeDocumentsCount->toBe(5)
        ->pendingDocumentsCount->toBe(2)
        ->failedDocumentsCount->toBe(0)
        ->sizeBytes->toBe(1048576)
        ->createTime->toBe('2025-11-23T10:00:00Z')
        ->updateTime->toBe('2025-11-23T11:00:00Z');
});

it('calculates size in MB correctly', function (): void {
    $storeData = GeminiFileSearchStoreData::from([
        'name' => 'test',
        'displayName' => 'Test',
        'activeDocumentsCount' => 1,
        'pendingDocumentsCount' => 0,
        'failedDocumentsCount' => 0,
        'sizeBytes' => 2097152, // 2 MB
        'createTime' => '2025-11-23T10:00:00Z',
        'updateTime' => '2025-11-23T10:00:00Z',
    ]);

    expect($storeData->getSizeMB())->toBe('2.00');
});

it('detects when store has documents', function (): void {
    $storeData = GeminiFileSearchStoreData::from([
        'name' => 'test',
        'displayName' => 'Test',
        'activeDocumentsCount' => 5,
        'pendingDocumentsCount' => 0,
        'failedDocumentsCount' => 0,
        'sizeBytes' => 1024,
        'createTime' => '2025-11-23T10:00:00Z',
        'updateTime' => '2025-11-23T10:00:00Z',
    ]);

    expect($storeData->hasDocuments())->toBeTrue();
});

it('detects when store has pending documents', function (): void {
    $storeData = GeminiFileSearchStoreData::from([
        'name' => 'test',
        'displayName' => 'Test',
        'activeDocumentsCount' => 0,
        'pendingDocumentsCount' => 3,
        'failedDocumentsCount' => 0,
        'sizeBytes' => 1024,
        'createTime' => '2025-11-23T10:00:00Z',
        'updateTime' => '2025-11-23T10:00:00Z',
    ]);

    expect($storeData->hasDocuments())->toBeTrue();
});

it('detects when store has no documents', function (): void {
    $storeData = GeminiFileSearchStoreData::from([
        'name' => 'test',
        'displayName' => 'Test',
        'activeDocumentsCount' => 0,
        'pendingDocumentsCount' => 0,
        'failedDocumentsCount' => 0,
        'sizeBytes' => 0,
        'createTime' => '2025-11-23T10:00:00Z',
        'updateTime' => '2025-11-23T10:00:00Z',
    ]);

    expect($storeData->hasDocuments())->toBeFalse();
});
