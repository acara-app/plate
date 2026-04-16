<?php

declare(strict_types=1);

use App\Services\Memory\MemoryFilterValidator;
use App\Services\Memory\VectorStoreService;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->service = new VectorStoreService(new MemoryFilterValidator);
});

it('returns 0 for identical empty vectors', function (): void {
    expect($this->service->cosineSimilarity([], []))->toBe(0.0);
});

it('returns 1 for identical unit vectors', function (): void {
    $v = [1.0, 0.0, 0.0, 0.0];

    expect($this->service->cosineSimilarity($v, $v))->toBeGreaterThan(0.999);
});

it('returns 0 for orthogonal vectors', function (): void {
    expect($this->service->cosineSimilarity([1.0, 0.0], [0.0, 1.0]))->toBe(0.0);
});

it('returns -1 for opposite vectors', function (): void {
    expect($this->service->cosineSimilarity([1.0, 0.0], [-1.0, 0.0]))->toBeLessThan(-0.999);
});

it('returns 0 for mismatched lengths', function (): void {
    expect($this->service->cosineSimilarity([1.0, 0.0], [1.0, 0.0, 0.0]))->toBe(0.0);
});

it('handles zero-magnitude vectors without division error', function (): void {
    expect($this->service->cosineSimilarity([0.0, 0.0], [1.0, 1.0]))->toBe(0.0);
});

it('detects when running on pgsql vs other drivers', function (): void {
    $driver = DB::connection()->getDriverName();

    expect($this->service->isPostgres())->toBe($driver === 'pgsql');
});
