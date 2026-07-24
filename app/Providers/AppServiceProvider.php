<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Contracts\Memory\ManagesMemoryContext;
use App\Contracts\Memory\PullsConversationHistory;
use App\Contracts\Services\IndexNowServiceContract;
use App\Contracts\Services\StripeServiceContract;
use App\Contracts\Skills\LoadsSkills;
use App\Events\AgentApprovalResolved;
use App\Listeners\NotifyTelegramOfApprovalOutcome;
use App\Listeners\TrackAiUsage;
use App\Models\User;
use App\Services\Billing\SubscriptionTierResolver;
use App\Services\IndexNowService;
use App\Services\Memory\NullConversationHistoryPuller;
use App\Services\Memory\NullMemoryExtractionDispatcher;
use App\Services\Memory\NullMemoryPromptContext;
use App\Services\Skills\NullSkillLoader;
use App\Services\StripeService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Cashier\Cashier;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StripeServiceContract::class, StripeService::class);
        $this->app->bind(IndexNowServiceContract::class, IndexNowService::class);
        $this->app->bind(ResolvesUserTier::class, SubscriptionTierResolver::class);
        $this->app->bindIf(ManagesMemoryContext::class, NullMemoryPromptContext::class);
        $this->app->bindIf(DispatchesMemoryExtraction::class, NullMemoryExtractionDispatcher::class);
        $this->app->bindIf(PullsConversationHistory::class, NullConversationHistoryPuller::class);
        $this->app->bindIf(LoadsSkills::class, NullSkillLoader::class);
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
        $this->bootVerificationDefaults();
        $this->bootCashierDefaults();
        $this->bootUrlDefaults();
        $this->bootRateLimiters();
        $this->configureDates();
        $this->registerEventListeners();
    }

    private function bootRateLimiters(): void
    {
        RateLimiter::for('snap-to-track-analyze', function (Request $request): Limit {
            $user = $request->user();

            if (! $user instanceof User) {
                return Limit::perHour($this->snapToTrackBurstCap(null))
                    ->by('snap-to-track-analyze:'.$request->ip());
            }

            return Limit::perHour($this->snapToTrackBurstCap($user))
                ->by('snap-to-track-analyze:'.$user->id);
        });
    }

    private function snapToTrackBurstCap(?User $user): int
    {
        $default = config()->integer('plate.snap_to_track.burst_caps.default', 5);

        if (! $user instanceof User) {
            return $default;
        }

        $entitlement = resolve(ResolvesUserTier::class)->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return $default;
        }

        return config()->integer(
            'plate.snap_to_track.burst_caps.'.$entitlement->tier->value,
            $default,
        );
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
        Model::shouldBeStrict(! $this->app->isProduction());
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

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(AgentPrompted::class, TrackAiUsage::class);
        Event::listen(AgentStreamed::class, TrackAiUsage::class);
        Event::listen(AgentApprovalResolved::class, NotifyTelegramOfApprovalOutcome::class);
    }
}
