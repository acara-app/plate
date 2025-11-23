<?php

declare(strict_types=1);

use App\DataObjects\GeminiUploadedFileData;

it('can be created from object using from method', function (): void {
    $file = (object) [
        'name' => 'files/abc123',
        'displayName' => 'Test File',
        'mimeType' => 'application/json',
        'sizeBytes' => 5242880,
        'uri' => 'https://example.com/files/abc123',
    ];

    $fileData = GeminiUploadedFileData::from($file);

    expect($fileData)
        ->name->toBe('files/abc123')
        ->displayName->toBe('Test File')
        ->mimeType->toBe('application/json')
        ->sizeBytes->toBe(5242880)
        ->uri->toBe('https://example.com/files/abc123');
});

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'files/xyz789',
        'displayName' => 'Another File',
        'mimeType' => 'text/plain',
        'sizeBytes' => 1024,
        'uri' => 'https://example.com/files/xyz789',
    ];

    $fileData = GeminiUploadedFileData::from($data);

    expect($fileData)
        ->name->toBe('files/xyz789')
        ->displayName->toBe('Another File')
        ->mimeType->toBe('text/plain')
        ->sizeBytes->toBe(1024)
        ->uri->toBe('https://example.com/files/xyz789');
});
