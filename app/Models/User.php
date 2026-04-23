<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\UserSettingsData;
use App\Enums\ChatPlatform;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read int $id
 * @property-read string|null $google_id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string|null $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read bool|null $is_verified
 * @property-read string|null $preferred_language
 * @property array<string, mixed>|null $settings
 * @property-read UserSettingsData $notification_settings
 * @property-read UserProfile|null $profile
 * @property-read Collection<int, MealPlan> $mealPlans
 * @property-read bool $is_onboarded
 * @property-read CarbonInterface|null $accepted_disclaimer_at
 * @property-read Collection<int, HealthDailyAggregate> $healthDailyAggregates
 * @property-read Collection<int, HealthSyncSample> $healthSyncSamples
 * @property-read bool $has_meal_plan
 */
#[Appends([
    'is_onboarded',
    'has_meal_plan',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
final class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use Billable, HasApiTokens, HasFactory, Notifiable, Prunable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->whereNull('email_verified_at')->where('created_at', '<=', now()->subDays(30));
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'google_id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'is_verified' => 'boolean',
            'preferred_language' => 'string',
            'locale' => 'string',
            'accepted_disclaimer_at' => 'datetime',
            'settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<UserProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * @return HasMany<MealPlan, $this>
     */
    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class)->latest();
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->latest();
    }

    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function paginatedConversations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->conversations()->paginate($perPage);
    }

    /**
     * @return HasMany<UserChatPlatformLink, $this>
     */
    public function chatPlatformLinks(): HasMany
    {
        return $this->hasMany(UserChatPlatformLink::class);
    }

    /**
     * @return HasOne<UserChatPlatformLink, $this>
     */
    public function activeChatPlatformLink(ChatPlatform $platform): HasOne
    {
        return $this->hasOne(UserChatPlatformLink::class)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->latestOfMany();
    }

    /**
     * @return HasMany<MobileSyncDevice, $this>
     */
    public function mobileSyncDevices(): HasMany
    {
        // @codeCoverageIgnoreStart
        return $this->hasMany(MobileSyncDevice::class);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return HasMany<HealthSyncSample, $this>
     */
    public function healthSyncSamples(): HasMany
    {
        return $this->hasMany(HealthSyncSample::class);
    }

    /**
     * @return HasMany<HealthDailyAggregate, $this>
     */
    // @codeCoverageIgnoreStart
    public function healthDailyAggregates(): HasMany
    {
        return $this->hasMany(HealthDailyAggregate::class);
    }

    // @codeCoverageIgnoreEnd

    /**
     * @return HasMany<SleepSession, $this>
     */
    public function sleepSessions(): HasMany
    {
        // @codeCoverageIgnoreStart
        return $this->hasMany(SleepSession::class);
        // @codeCoverageIgnoreEnd
    }

    public function resolveTimezone(): string
    {
        // @codeCoverageIgnoreStart
        $tz = $this->getAttributeValue('timezone');

        return is_string($tz) && $tz !== '' ? $tz : 'UTC';
        // @codeCoverageIgnoreEnd
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->get()->contains(fn (Subscription $subscription): bool => $subscription->valid()); // @phpstan-ignore-line
    }

    public function activeSubscription(): ?Subscription
    {
        /** @var Subscription|null $subscription */
        $subscription = $this->subscriptions()->get()->first(fn (Subscription $subscription): bool => $subscription->valid()); // @phpstan-ignore-line

        return $subscription;
    }

    public function subscriptionDisplayName(): ?string
    {
        $subscription = $this->activeSubscription();

        if (! $subscription instanceof Subscription) {
            return null;
        }

        return str($subscription->type)->title()->replace('-', ' ')->toString();
    }

    protected function getIsVerifiedAttribute(?bool $isVerified): bool
    {
        if (collect(config()->array('sponsors.admin_emails'))->contains($this->email)) {
            return true;
        }

        if ($this->hasActiveSubscription()) {
            return true;
        }

        if ($isVerified === null) {
            return false;
        }

        return $isVerified;
    }

    protected function getHasMealPlanAttribute(): bool
    {
        return $this->mealPlans()->exists();
    }

    protected function getIsOnboardedAttribute(): bool
    {
        return $this->profile->onboarding_completed ?? false;
    }

    protected function getNotificationSettingsAttribute(): UserSettingsData
    {
        // @codeCoverageIgnoreStart
        return UserSettingsData::from($this->settings ?? []);
        // @codeCoverageIgnoreEnd
    }
}
