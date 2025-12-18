<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\Contracts\StripeServiceInterface;
use App\Services\StripeService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StripeServiceInterface::class, StripeService::class);
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
        $this->bootVerificationDefaults();
        $this->bootCashierDefaults();
        $this->bootUrlDefaults();
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

    private function bootCashierDefaults(): void
    {
        Cashier::useCustomerModel(User::class);
    }

    private function bootUrlDefaults(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
