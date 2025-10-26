# Cloudflare Cache Purging Setup

This document explains how to set up automatic Cloudflare cache purging after deployments to ensure new assets are immediately available to users.

## Why This is Important

When you deploy new assets to your R2 bucket (`build-plate-assets.acara.app`), Cloudflare caches these assets for performance. Without purging the cache, users may continue seeing old versions of your assets, which can cause issues like:

- CORS errors (if CORS configuration was updated)
- Missing features from new JavaScript bundles
- Outdated styles from CSS files
- Module loading failures

## Setup Instructions

### Step 1: Create a Cloudflare API Token (Recommended)

1. Go to [Cloudflare API Tokens](https://dash.cloudflare.com/profile/api-tokens)
2. Click **"Create Token"**
3. Click **"Get started"** next to **"Create Custom Token"**
4. Configure the token:
   - **Token name**: `Asset Cache Purge - Plate App`
   - **Permissions**: 
     - Zone → Cache Purge → Purge
   - **Zone Resources**:
     - Include → All zones (or select specific zone: `build-plate-assets.acara.app`)
5. Click **"Continue to summary"**
6. Click **"Create Token"**
7. **Copy the token** (you won't be able to see it again!)

### Step 2: Get Your Zone ID

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Select your domain: **`build-plate-assets.acara.app`**
3. Scroll down on the Overview page
4. In the **API** section on the right, copy the **Zone ID**

### Step 3: Add Credentials to Production Environment

Add these lines to your `.env.production` file:

```bash
# Cloudflare API (for cache purging after deployments)
CLOUDFLARE_API_TOKEN=your_api_token_from_step_1
CLOUDFLARE_ZONE_ID=your_zone_id_from_step_2
```

### Step 4: Re-encrypt Your .env.production

After adding the Cloudflare credentials, re-encrypt your production environment file:

```bash
php artisan env:encrypt --env=production --force
```

This will update the encrypted `.env.production.encrypted` file that gets deployed.

### Step 5: Deploy

On your next deployment, the cache will be automatically purged after assets are published.

## How It Works

The deployment script (`ploi-deployment.sh`) now includes:

```bash
npm run build
php artisan vite:publish

# Purge Cloudflare cache for asset bucket
bash scripts/purge-cloudflare-cache.sh
```

The `purge-cloudflare-cache.sh` script:
1. Checks if Cloudflare API credentials are configured
2. If configured, purges all cache for the zone
3. If not configured, skips with a warning (deployment continues)

## Manual Cache Purging

You can also manually purge the cache anytime:

```bash
# On production server
bash scripts/purge-cloudflare-cache.sh

# Or from your local machine with production credentials
export CLOUDFLARE_API_TOKEN=your_token
export CLOUDFLARE_ZONE_ID=your_zone_id
bash scripts/purge-cloudflare-cache.sh
```

## Testing the Script

Test locally before deploying:

```bash
# Load production environment variables
export $(grep -v '^#' .env.production | xargs)

# Run the script
bash scripts/purge-cloudflare-cache.sh
```

## Troubleshooting

### "Cloudflare API credentials not found"

The script will skip cache purging with a warning. Add the credentials following steps above.

### "Failed to purge cache"

Check that:
1. Your API token has the correct permissions (Cache Purge → Purge)
2. The Zone ID matches your `build-plate-assets.acara.app` domain
3. The API token is not expired
4. The token has access to the specific zone (or all zones)

### Verify CORS is Working

After deployment, verify CORS headers:

```bash
bash scripts/verify-cors.sh
```

## Alternative: Legacy API Key Method

If you prefer using a Global API Key instead of an API Token:

```bash
# In .env.production
CLOUDFLARE_API_KEY=your_global_api_key
CLOUDFLARE_EMAIL=your_cloudflare_email
CLOUDFLARE_ZONE_ID=your_zone_id
```

**Note:** API Tokens are recommended as they're more secure (limited permissions and scope).

## Security Note

The `.env.production` file is **encrypted** before being committed to the repository. The encryption key (`LARAVEL_ENV_ENCRYPTION_KEY`) is stored securely on your production server and used during deployment to decrypt the environment file.

Never commit unencrypted API tokens to your repository.
