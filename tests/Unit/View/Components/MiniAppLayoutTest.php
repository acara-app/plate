<?php

declare(strict_types=1);

use App\View\Components\MiniAppLayout;

it('renders the mini-app layout view', function (): void {
    $component = new MiniAppLayout;
    $view = $component->render();

    expect($view->getName())->toBe('layouts.mini-app');
});
