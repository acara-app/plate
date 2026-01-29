<?php

declare(strict_types=1);

afterEach(function (): void {
    $filePath = storage_path('app/test-foods.txt');
    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

it('fails when no foods are provided', function (): void {
    $this->artisan('seo:generate-food')
        ->assertFailed()
        ->expectsOutputToContain('No foods specified');
});

it('shows what would be processed in dry-run mode using --from-file', function (): void {
    $filePath = storage_path('app/test-foods.txt');
    file_put_contents($filePath, 'Banana');

    $this->artisan('seo:generate-food', ['--from-file' => $filePath, '--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Foods that would be processed')
        ->expectsOutputToContain('Banana');
});
