# CORS Issue Resolution Summary

## Issue
CORS errors when loading JavaScript modules from R2 bucket (`build-plate-assets.acara.app`) to the main application (`plate.acara.app`):
```
Access to script at 'https://build-plate-assets.acara.app/build/assets/dashboard-R9lmxa37.js' 
from origin 'https://plate.acara.app' has been blocked by CORS policy: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Root Cause
The browser was **not sending the `Origin` header** when fetching JavaScript modules and prefetched assets. R2's CORS implementation only returns CORS headers (`Access-Control-Allow-Origin`) when the request includes an `Origin` header.

**Why the Origin header was missing:**
- JavaScript module imports (dynamic imports) don't automatically include the `Origin` header
- `<link rel="prefetch">` requests don't include the `Origin` header by default
- Without `crossorigin="anonymous"` attribute on script tags, browsers don't treat them as CORS requests

**Verification:**
```bash
# Without Origin header - NO CORS headers returned
curl -I "https://build-plate-assets.acara.app/build/assets/dashboard-R9lmxa37.js"
# Returns: HTTP/2 200 (but no access-control-allow-origin)

# With Origin header - CORS headers ARE returned
curl -I "https://build-plate-assets.acara.app/build/assets/dashboard-R9lmxa37.js" -H "Origin: https://plate.acara.app"
# Returns: access-control-allow-origin: https://plate.acara.app ✅
```

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

### 2. Added `crossorigin="anonymous"` to Script Tags (Critical Fix)
Updated `resources/views/app.blade.php` to ensure all Vite-generated script tags include the `crossorigin` attribute:

```blade
@vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"], attributes: ['crossorigin' => 'anonymous'])
```

**What this does:**
- Forces the browser to send the `Origin` header with script requests
- Enables CORS mode for all JavaScript module imports
- Ensures prefetch requests also include the `Origin` header
- Allows the R2 bucket to return proper CORS headers

### 3. Automatic Cache Purging
Added automatic Cloudflare cache purging after deployments:
- Created `scripts/purge-cloudflare-cache.sh`
- Updated `ploi-deployment.sh` to purge cache after `vite:publish`
- Added documentation in `docs/cloudflare-cache-purging.md`

### 4. Scripts Added
- `scripts/verify-cors.sh` - Verify CORS configuration is working
- `scripts/purge-cloudflare-cache.sh` - Purge Cloudflare cache manually or automatically

## Deployment Steps

1. **Commit changes**:
   ```bash
   git add .
   git commit -m "Fix CORS issue by adding crossorigin attribute to script tags"
   git push origin main
   ```

2. **Environment variables** (if not done yet):
   Add to `.env.production`:
   ```bash
   CLOUDFLARE_API_TOKEN=your_token_here
   CLOUDFLARE_ZONE_ID=your_zone_id_here
   ```

3. **Re-encrypt**:
   ```bash
   php artisan env:encrypt --env=production --force
   ```

4. **Deploy** - The deployment script will:
   - Build new assets with crossorigin attribute
   - Publish assets to R2
   - Automatically purge Cloudflare cache

## Testing After Deployment

1. **Clear browser cache** (important - one last time):
   - Hard Refresh: `Cmd + Shift + R` (macOS) or `Ctrl + Shift + R` (Windows)
   - Or DevTools → Right-click refresh → "Empty Cache and Hard Reload"

2. **Verify CORS headers**:
   ```bash
   bash scripts/verify-cors.sh
   ```

3. **Check browser DevTools**:
   - Network tab → Look for script requests
   - Should see `crossorigin` attribute in the HTML source
   - No more CORS errors in console

## Why This Works

Before fix:
```
Browser → GET /build/assets/dashboard-R9lmxa37.js (no Origin header)
R2 → 200 OK (no CORS headers returned)
Browser → ❌ CORS error: No 'Access-Control-Allow-Origin' header
```

After fix:
```
Browser → GET /build/assets/dashboard-R9lmxa37.js + Origin: https://plate.acara.app
R2 → 200 OK + access-control-allow-origin: https://plate.acara.app
Browser → ✅ Success!
```

## Files Changed

- ✅ `.env.production` - Added Cloudflare API credentials
- ✅ `ploi-deployment.sh` - Added cache purge step
- ✅ `resources/views/app.blade.php` - **Added crossorigin attribute (critical fix)**
- ✅ `scripts/purge-cloudflare-cache.sh` - New cache purge script
- ✅ `scripts/verify-cors.sh` - New CORS verification script
- ✅ `docs/cloudflare-cache-purging.md` - New documentation
- ✅ `docs/cors-issue-resolution.md` - This document

## References

- [MDN: CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [MDN: crossorigin attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/crossorigin)
- [Cloudflare R2 CORS](https://developers.cloudflare.com/r2/buckets/cors/)
- [Laravel Vite Integration](https://laravel.com/docs/12.x/vite)
