<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Waterline\WaterlineApplicationServiceProvider;

final class WaterlineServiceProvider extends WaterlineApplicationServiceProvider
{
    public function gate(): void
    {
        Gate::define('viewWaterline', fn (User $user): bool => in_array($user->email, config()->array('sponsors.admin_emails')));
    }
}
