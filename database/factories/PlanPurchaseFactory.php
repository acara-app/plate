<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlanPurchase;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlanPurchase>
 */
final class PlanPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $session = 'cs_test_'.Str::random(24);

        return [
            'subscription_product_id' => SubscriptionProduct::factory(),
            'snap_journey_id' => null,
            'guest_hash' => hash('sha256', (string) Str::uuid()),
            'placement' => 'result',
            'stripe_checkout_session_id' => $session,
            'checkout_url' => 'https://checkout.stripe.com/c/pay/'.$session,
            'checkout_expires_at' => now()->addDay(),
        ];
    }

    public function paid(string $email = 'buyer@example.com'): self
    {
        return $this->state(fn (): array => [
            'stripe_customer_id' => 'cus_'.Str::random(14),
            'stripe_subscription_id' => 'sub_'.Str::random(14),
            'email' => $email,
            'paid_at' => now(),
        ]);
    }

    public function claimed(User $user): self
    {
        return $this->paid($user->email)->state(fn (): array => [
            'user_id' => $user->id,
            'claimed_at' => now(),
            'claim_method' => 'email_link',
        ]);
    }
}
