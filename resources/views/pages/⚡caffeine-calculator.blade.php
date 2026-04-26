<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.', 'metaKeywords' => 'caffeine calculator, safe caffeine dose, caffeine sleep cutoff, coffee calculator, caffeine half life'])]
#[Title('Caffeine Calculator | Free Safe Dose & Sleep Cutoff Tool')]
class extends Component
{
    //
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12">
    <h1 class="text-3xl font-bold tracking-tight">Caffeine Calculator</h1>
    <p class="mt-4 text-gray-600">
        Estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.
    </p>
</div>
