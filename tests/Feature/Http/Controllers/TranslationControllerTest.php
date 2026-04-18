<?php

declare(strict_types=1);

use App\Http\Controllers\TranslationController;

covers(TranslationController::class);

it('returns translations for a supported locale', function (): void {
    $this->get(route('translations.show', ['locale' => 'en']))
        ->assertOk()
        ->assertJsonStructure(['common', 'auth', 'validation']);
});

it('returns 404 for an unsupported locale', function (): void {
    $this->get(route('translations.show', ['locale' => 'xx']))
        ->assertNotFound();
});
