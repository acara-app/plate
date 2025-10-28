<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

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
 * @property-read UserProfile|null $profile
 * @property-read Collection<int, MealPlan> $mealPlans
 * @property-read Collection<int, JobTracking> $jobTrackings
 * @property-read bool $is_onboarded
 * @property-read bool $has_meal_plan
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $appends = [
        'is_onboarded',
        'has_meal_plan',
    ];

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

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
     * @return HasMany<JobTracking, $this>
     */
    public function jobTrackings(): HasMany
    {
        return $this->hasMany(JobTracking::class)->latest();
    }

    /**
     * Check if the user has any active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->whereStripeStatus('active')->exists();
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription(): ?\Laravel\Cashier\Subscription
    {
        /** @var \Laravel\Cashier\Subscription|null $subscription */
        $subscription = $this->subscriptions()->whereStripeStatus('active')->first();

        return $subscription;
    }

    /**
     * Get a user-friendly subscription type name.
     */
    public function subscriptionDisplayName(): ?string
    {
        $subscription = $this->activeSubscription();

        if (! $subscription instanceof \Laravel\Cashier\Subscription) {
            return null;
        }

        // Convert slug back to title case (e.g., 'premium-plan' -> 'Premium Plan')
        return str($subscription->type)->title()->replace('-', ' ')->toString();
    }

    protected function getHasMealPlanAttribute(): bool
    {
        return $this->mealPlans()->exists();
    }

    /**
     * Get the user's "onboarding_completed" attribute.
     */
    protected function getIsOnboardedAttribute(): bool
    {
        return $this->profile->onboarding_completed ?? false;
    }
}
