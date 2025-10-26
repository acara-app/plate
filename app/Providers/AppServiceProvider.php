<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
        $this->bootVerificationDefaults();
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
    }

    private function bootPasswordDefaults(): void
    {
        Password::defaults(fn () => app()->isLocal() || app()->runningUnitTests() ? Password::min(12)->max(255) : Password::min(12)->max(255)->uncompromised());
    }

    private function bootVerificationDefaults(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            /** @var Model&\Illuminate\Contracts\Auth\MustVerifyEmail $notifiable */
            $relativeUrl = URL::signedRoute(
                'verification.verify',
                ['id' => $notifiable->getKey(), 'hash' => sha1((string) $notifiable->getEmailForVerification())],
                absolute: false
            );

            return url($relativeUrl);
        });
    }
}
