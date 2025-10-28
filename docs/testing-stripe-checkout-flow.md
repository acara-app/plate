# Testing Stripe Checkout Subscription Flow

## Prerequisites

1. Fix the webhook secret issue (see `stripe-webhook-local-testing.md`)
2. Make sure `stripe listen --forward-to http://plate.test/stripe/webhook` is running
3. Ensure webhook returns `[200]` responses

## Complete Testing Flow

### Option 1: Test with Real Checkout UI

1. **Start the checkout flow in your app:**
   ```
   Visit: http://plate.test/checkout/subscription
   Select a plan and click subscribe
   ```

2. **Use Stripe test cards on checkout page:**
   - Success: `4242 4242 4242 4242`
   - Any future expiry date (e.g., 12/34)
   - Any 3-digit CVC
   - Any ZIP code

3. **Watch the stripe listen terminal:**
   ```
   --> checkout.session.completed [evt_xxx]
   <-- [200] POST http://plate.test/stripe/webhook
   --> customer.subscription.created [evt_xxx]
   <-- [200] POST http://plate.test/stripe/webhook
   ```

4. **Verify database records:**
   ```bash
   php artisan tinker
   ```
   ```php
   DB::table('subscriptions')->get();
   DB::table('subscription_items')->get();
   // Or using Eloquent:
   User::find(1)->subscriptions;
   ```

### Option 2: Test with Stripe CLI Trigger

1. **Trigger the checkout completion:**
   ```bash
   stripe trigger checkout.session.completed
   ```

2. **Watch for webhooks:**
   - You'll see multiple events: `product.created`, `price.created`, `charge.succeeded`, `payment_intent.succeeded`, `checkout.session.completed`, `customer.subscription.created`
   - **The important one:** `customer.subscription.created` should return `[200]`

3. **Check the database:**
   ```bash
   php artisan tinker
   ```
   ```php
   // Check if subscription was created
   App\Models\User::first()?->subscriptions()->get();
   
   // Or check raw tables
   DB::table('subscriptions')->get();
   DB::table('subscription_items')->get();
   ```

## What Should Happen

### Subscriptions Table
Should contain a record like:
```
id: 1
user_id: 1
type: 'default'
stripe_id: 'sub_xxx'
stripe_status: 'active' or 'trialing'
stripe_price: 'price_xxx'
quantity: 1
trial_ends_at: null
ends_at: null
created_at: timestamp
updated_at: timestamp
```

### Subscription Items Table
Should contain a record like:
```
id: 1
subscription_id: 1
stripe_id: 'si_xxx'
stripe_product: 'prod_xxx'
stripe_price: 'price_xxx'
quantity: 1
created_at: timestamp
updated_at: timestamp
```

## Troubleshooting

### No Records Created After Checkout

**Symptom:** User completes checkout, but tables are empty.

**Check:**
1. Is `stripe listen` running?
2. Are webhooks returning `[200]`? (Not `[403]`)
3. Was `customer.subscription.created` webhook sent?

**Solutions:**
```bash
# 1. Check webhook secret
php artisan tinker
config('cashier.webhook.secret');

# 2. Clear config cache
php artisan config:clear

# 3. Check application logs for errors
tail -f storage/logs/laravel.log

# 4. Manually trigger the webhook
stripe trigger customer.subscription.created
```

### Incomplete Test Subscription

**Symptom:** Subscription exists in Stripe but not in local DB.

**Solution:**
Trigger the webhook manually:
```bash
stripe trigger customer.subscription.created
```

### User Already Has Subscription

**Symptom:** Error: "You already have an active subscription"

**Check database:**
```bash
php artisan tinker
App\Models\User::find(1)->subscriptions()->delete();
```

## Verifying Subscription Status

```php
// In tinker or controller
$user = User::find(1);

// Check if user has any subscription
$user->subscribed('default'); // true/false

// Get active subscription
$subscription = $user->subscription('default');

// Check subscription details
$subscription->stripe_status; // 'active', 'trialing', etc.
$subscription->active(); // true/false
$subscription->onTrial(); // true/false
$subscription->canceled(); // true/false
$subscription->onGracePeriod(); // true/false

// Get subscription items
$subscription->items; // Collection of subscription items
```

## Common Stripe CLI Commands

```bash
# Listen for webhooks
stripe listen --forward-to http://plate.test/stripe/webhook

# Trigger specific events
stripe trigger checkout.session.completed
stripe trigger customer.subscription.created
stripe trigger customer.subscription.updated
stripe trigger customer.subscription.deleted
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed

# View recent events
stripe events list

# Get details of a specific event
stripe events retrieve evt_xxx
```

## Production Checklist

Before going to production:

- [ ] Configure webhook endpoint in Stripe Dashboard
- [ ] Set production `STRIPE_WEBHOOK_SECRET` in `.env`
- [ ] Test webhook delivery from Stripe Dashboard
- [ ] Enable all required webhook events
- [ ] Set up monitoring for failed webhooks
- [ ] Configure proper error handling
- [ ] Test subscription lifecycle (create, update, cancel)
