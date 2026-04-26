<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.', 'metaKeywords' => 'caffeine calculator, safe caffeine dose, caffeine sleep cutoff, coffee calculator, caffeine half life'])]
#[Title('Coffee Caffeine Calculator: How Much Is Too Much?')]
class extends Component
{
    //
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12">
    <h1 class="text-[32px] font-bold leading-tight tracking-tight md:text-5xl">
        Coffee Caffeine Calculator: How Much Is Too Much?
    </h1>
    <p class="mt-4 text-lg text-gray-600">
        Choose your drink, tell us about you, and find your safe daily limit.
    </p>
</div>
