<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;

it('loads lazy chunks on demand instead of prefetching them all after a page loads', function (): void {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('/build/assets/', false)
        ->assertDontSee('prefetch', false);
});
