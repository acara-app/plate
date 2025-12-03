<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Waterline\WaterlineApplicationServiceProvider;

final class WaterlineServiceProvider extends WaterlineApplicationServiceProvider
{
    /**
     * Register the Waterline gate.
     *
     * This gate determines who can access Waterline in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewWaterline', fn ($user): bool => in_array($user->email, config()->array('sponsors.admin_emails')));
    }
}
