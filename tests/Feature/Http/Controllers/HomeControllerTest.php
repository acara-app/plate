<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;

it('displays the homepage', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertViewHas('featuredFoods');
});

it('passes featured foods to the view', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();

    $featuredFoods = $response->viewData('featuredFoods');

    expect($featuredFoods)->toBeInstanceOf(Collection::class);
});
