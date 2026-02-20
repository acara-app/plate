<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Services\IndexNowServiceContract;
use App\Contracts\Services\StripeServiceContract;
use App\Models\User;
use App\Services\IndexNowService;
use App\Services\StripeService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StripeServiceContract::class, StripeService::class);
        $this->app->bind(IndexNowServiceContract::class, IndexNowService::class);
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
        $this->bootVerificationDefaults();
        $this->bootCashierDefaults();
        $this->bootUrlDefaults();
        $this->configureDates();
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
            /** @var Model&MustVerifyEmail $notifiable */
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

    /**
     * Configure the dates.
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }
}
