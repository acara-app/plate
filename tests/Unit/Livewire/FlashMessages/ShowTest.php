<?php

declare(strict_types=1);

use App\Livewire\FlashMessages\Show;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(Show::class)
        ->assertStatus(200);
});
