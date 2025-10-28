# Stripe Webhook Local Testing

## The 403 Error Issue

When you see 403 errors in your `stripe listen` output, it means the webhook signature verification is failing. This happens when the `STRIPE_WEBHOOK_SECRET` in your `.env` file doesn't match the webhook signing secret from your Stripe CLI session.

## How to Fix

### Step 1: Start Stripe Listen
```bash
stripe listen --forward-to http://plate.test/stripe/webhook
```

### Step 2: Copy the Webhook Secret
When you run the command above, Stripe CLI will display output like:
```
Ready! Your webhook signing secret is whsec_abc123xyz... (^C to quit)
```

Copy the webhook secret (starts with `whsec_`).

### Step 3: Update Your .env File
Update the `STRIPE_WEBHOOK_SECRET` in your `.env` file:
```env
STRIPE_WEBHOOK_SECRET=whsec_abc123xyz...
```

Replace `whsec_abc123xyz...` with the actual secret from Step 2.

### Step 4: Clear Config Cache (if needed)
```bash
php artisan config:clear
```

### Step 5: Test the Webhook
```bash
stripe trigger checkout.session.completed
```

You should now see `[200]` responses instead of `[403]`:
```
2025-10-26 17:17:57   --> checkout.session.completed [evt_1SMdCDFjKo53Z6zoAs4mdXTp]
2025-10-26 17:17:57  <--  [200] POST http://plate.test/stripe/webhook [evt_1SMdCDFjKo53Z6zoAs4mdXTp]
```

## Important Notes

- The webhook secret from `stripe listen` is **temporary** and only valid for that CLI session
- Each time you restart `stripe listen`, you may get a **new webhook secret**
- For production, you'll configure a permanent webhook secret in your Stripe Dashboard
- The webhook route (`/stripe/webhook`) is automatically registered by Laravel Cashier
- The `VerifyWebhookSignature` middleware automatically validates incoming webhooks

## Production Setup

For production, you need to:
1. Create a webhook endpoint in your Stripe Dashboard
2. Point it to `https://yourdomain.com/stripe/webhook`
3. Copy the webhook signing secret from the Dashboard
4. Set it as `STRIPE_WEBHOOK_SECRET` in your production `.env`

## Troubleshooting

If you still see 403 errors after updating the secret:
1. Make sure you copied the complete secret (including `whsec_` prefix)
2. Run `php artisan config:clear` to clear any cached config
3. Verify the secret is correctly set with: `php artisan tinker` then `config('cashier.webhook.secret')`
4. Restart your `stripe listen` command and get a fresh secret

### 🧪 Testing the Complete Flow

Make sure you have both terminals running:

Terminal 1:
```
stripe listen --forward-to http://plate.test/stripe/webhook
```

Terminal 2 (if needed):
```
php artisan queue:work
```

Then:
```
Go to http://plate.test/checkout/subscription
Click on a subscription product
Use Stripe test card: 4242 4242 4242 4242
Any future date (e.g., 12/34)
Any 3-digit CVC (e.g., 123)
Complete the checkout
```

