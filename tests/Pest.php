<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void {}
