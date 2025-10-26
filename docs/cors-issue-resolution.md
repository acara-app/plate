# CORS Issue Resolution Summary

## Issue
CORS errors when loading JavaScript modules from R2 bucket (`build-plate-assets.acara.app`) to the main application (`plate.acara.app`).

## Root Cause
Browser cache was serving cached responses from before the CORS configuration was properly set up.

## Verification
CORS headers are now working correctly on the server:
- ✅ `access-control-allow-origin: https://plate.acara.app`
- ✅ `vary: Origin, Accept-Encoding`
- ✅ `access-control-expose-headers: Cache-Control,Content-Type,ETag,Content-Length`
- ✅ OPTIONS preflight working with `access-control-allow-methods: GET, HEAD`

Run `bash scripts/verify-cors.sh` to verify anytime.

## Solution Implemented

### 1. R2 Bucket CORS Policy
Updated to allow requests from `plate.acara.app`:

```json
[
  {
    "AllowedOrigins": [
      "https://plate.acara.app",
      "https://*.acara.app"
    ],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": [
      "Cache-Control",
      "Content-Type",
      "ETag",
      "Content-Length"
    ],
    "MaxAgeSeconds": 3600
  }
]
```

### 2. Automatic Cache Purging
Added automatic Cloudflare cache purging after deployments:
- Created `scripts/purge-cloudflare-cache.sh`
- Updated `ploi-deployment.sh` to purge cache after `vite:publish`
- Added documentation in `docs/cloudflare-cache-purging.md`

### 3. Scripts Added
- `scripts/verify-cors.sh` - Verify CORS configuration is working
- `scripts/purge-cloudflare-cache.sh` - Purge Cloudflare cache manually or automatically

## Next Steps

1. **Add Cloudflare API credentials** to `.env.production`:
   ```bash
   CLOUDFLARE_API_TOKEN=your_token
   CLOUDFLARE_ZONE_ID=your_zone_id
   ```
   
2. **Follow setup guide**: `docs/cloudflare-cache-purging.md`

3. **Re-encrypt environment file**:
   ```bash
   php artisan env:encrypt --env=production --force
   ```

4. **Deploy** - Cache will be automatically purged

## For Users Experiencing the Issue Now

Since CORS is working on the server, users just need to clear their browser cache:

- **Hard Refresh**: `Cmd + Shift + R` (macOS) or `Ctrl + Shift + R` (Windows)
- **Chrome DevTools**: Right-click refresh → "Empty Cache and Hard Reload"
- **Disable Cache**: DevTools → Network tab → Check "Disable cache"

New users won't experience this issue.

## Files Changed

- ✅ `.env.production` - Added Cloudflare API placeholders
- ✅ `ploi-deployment.sh` - Added cache purge step
- ✅ `scripts/purge-cloudflare-cache.sh` - New cache purge script
- ✅ `scripts/verify-cors.sh` - New CORS verification script
- ✅ `docs/cloudflare-cache-purging.md` - New documentation

## Testing

Verify everything is working:

```bash
# Test CORS headers
bash scripts/verify-cors.sh

# Test cache purge (after adding credentials)
bash scripts/purge-cloudflare-cache.sh
```
