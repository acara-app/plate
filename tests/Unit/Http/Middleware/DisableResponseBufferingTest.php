<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\DisableResponseBuffering;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

test('it sets the correct headers to disable buffering', function () {
    $middleware = new DisableResponseBuffering();

    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return new Response('content');
    });

    expect($response->headers->get('X-Accel-Buffering'))->toBe('no')
        ->and($response->headers->get('Cache-Control'))->toBe('must-revalidate, no-cache, no-store, private')
        ->and($response->headers->get('Connection'))->toBe('keep-alive');
});
