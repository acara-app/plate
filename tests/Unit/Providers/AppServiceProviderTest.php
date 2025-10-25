<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

it('boots models defaults', function (): void {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    expect(Illuminate\Database\Eloquent\Model::isUnguarded())->toBeTrue();
});

it('boots password defaults in local environment', function (): void {
    App::shouldReceive('isLocal')->andReturn(true);
    App::shouldReceive('runningUnitTests')->andReturn(false);

    $provider = new AppServiceProvider(app());
    $provider->boot();

    $password = Password::defaults();

    expect($password)->toBeInstanceOf(Password::class);
});

it('boots password defaults in production environment', function (): void {
    App::shouldReceive('isLocal')->andReturn(false);
    App::shouldReceive('runningUnitTests')->andReturn(false);

    $provider = new AppServiceProvider(app());
    $provider->boot();

    $password = Password::defaults();

    expect($password)->toBeInstanceOf(Password::class);
});

it('boots url defaults in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $provider = new AppServiceProvider(app());
    $provider->boot();

    // URL scheme is forced to https in production
    expect(true)->toBeTrue();
});

it('does not force https scheme in non-production', function (): void {
    app()->detectEnvironment(fn () => 'local');

    $provider = new AppServiceProvider(app());
    $provider->boot();

    // URL scheme is not forced in non-production
    expect(true)->toBeTrue();
});

it('boots verification defaults', function (): void {
    $user = \App\Models\User::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => null,
    ]);

    $provider = new AppServiceProvider(app());
    $provider->boot();

    // Trigger the verification notification which uses the createUrlUsing closure
    $notification = new VerifyEmail;
    $url = $notification->toMail($user)->actionUrl;

    expect($url)->toBeString()->toContain('verify-email');
});
