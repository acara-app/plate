<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PlanPurchaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property-read string $id
 * @property int $subscription_product_id
 * @property string|null $snap_journey_id
 * @property string $guest_hash
 * @property string $placement
 * @property string|null $stripe_checkout_session_id
 * @property string|null $checkout_url
 * @property CarbonInterface|null $checkout_expires_at
 * @property CarbonInterface|null $checkout_returned_at
 * @property string|null $stripe_customer_id
 * @property string|null $stripe_subscription_id
 * @property string|null $email
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $cancelled_at
 * @property string|null $claim_token_hash
 * @property CarbonInterface|null $claim_token_expires_at
 * @property string|null $claim_conflict
 * @property int|null $user_id
 * @property CarbonInterface|null $claimed_at
 * @property string|null $claim_method
 * @property-read SubscriptionProduct $product
 * @property-read User|null $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PlanPurchase extends Model
{
    /** @use HasFactory<PlanPurchaseFactory> */
    use HasFactory;

    use HasUlids;
    use MassPrunable;

    public const int CLAIM_LINK_MINUTES = 60;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByClaimToken(string $token): ?self
    {
        return self::query()->where('claim_token_hash', self::hashToken($token))->first();
    }

    public function issueClaimToken(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'claim_token_hash' => self::hashToken($token),
            'claim_token_expires_at' => now()->addMinutes(self::CLAIM_LINK_MINUTES),
        ])->save();

        return $token;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function claimLinkExpired(): bool
    {
        return $this->claim_token_expires_at === null || $this->claim_token_expires_at->isPast();
    }

    public function canReopenCheckout(): bool
    {
        return ! $this->isPaid() && ! $this->isCancelled() && $this->checkout_url !== null
            && $this->checkout_expires_at?->isFuture() === true;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->checkout_returned_at !== null && $this->canReopenCheckout();
    }

    public function maskedEmail(): ?string
    {
        if ($this->email === null) {
            return null;
        }

        $name = Str::before($this->email, '@');

        return Str::substr($name, 0, 1).'•••@'.Str::after($this->email, '@');
    }

    public function reference(): string
    {
        return Str::upper(Str::substr($this->id, -8));
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->whereNull('paid_at')->where('created_at', '<', now()->subDays(7));
    }

    /**
     * @return BelongsTo<SubscriptionProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SubscriptionProduct::class, 'subscription_product_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'subscription_product_id' => 'integer',
            'user_id' => 'integer',
            'checkout_expires_at' => 'datetime',
            'checkout_returned_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'claim_token_expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }
}
