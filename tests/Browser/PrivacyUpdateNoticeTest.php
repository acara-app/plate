<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('dismisses the privacy update notice', function (): void {
    $this->actingAs(User::factory()->create(['created_at' => '2026-10-01 09:00:00']));

    visit(route('dashboard', absolute: false))
        ->assertSee("We've updated our Privacy Policy.")
        ->click('[aria-label="Dismiss privacy update"]')
        ->assertDontSee("We've updated our Privacy Policy.")
        ->assertNoJavaScriptErrors();
});
