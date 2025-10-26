# 🚀 Deploy CORS Fix - Quick Reference

## The Problem
Browser wasn't sending `Origin` header → R2 didn't return CORS headers → CORS error

## The Solution
Added `crossorigin="anonymous"` to script tags → Browser sends `Origin` header → R2 returns CORS headers → ✅ Success!

## Deployment Checklist

### 1. ✅ Code Changes (Already Done)
- [x] Updated `resources/views/app.blade.php` with crossorigin attribute
- [x] Created cache purge script
- [x] Updated deployment script
- [x] Added Cloudflare API credentials to `.env.production`

### 2. 🔐 Re-encrypt Environment File
```bash
php artisan env:encrypt --env=production --force
```

### 3. 📤 Push to Repository
```bash
git add .
git commit -m "Fix CORS issue by adding crossorigin attribute to script tags"
git push origin main
```

### 4. 🚀 Deploy
Your deployment script will automatically:
- ✅ Pull latest code
- ✅ Install dependencies
- ✅ Build assets with crossorigin attribute
- ✅ Publish assets to R2
- ✅ Purge Cloudflare cache
- ✅ Deploy application

### 5. 🧪 Test After Deployment

**Clear your browser cache one last time:**
- Chrome: `Cmd + Shift + R` (Mac) or `Ctrl + Shift + R` (Windows)
- Or: DevTools → Right-click refresh → "Empty Cache and Hard Reload"

**Verify the fix:**
1. Open Chrome DevTools → Network tab
2. Navigate to https://plate.acara.app/login
3. Look for requests to `build-plate-assets.acara.app`
4. Check Response Headers - should see `access-control-allow-origin: https://plate.acara.app`
5. ✅ No CORS errors in console!

**Run verification script:**
```bash
bash scripts/verify-cors.sh
```

## Expected Behavior After Fix

### ✅ Before (curl test):
```bash
curl -I "https://build-plate-assets.acara.app/build/assets/dashboard-R9lmxa37.js" \
  -H "Origin: https://plate.acara.app"
```
Response includes:
```
access-control-allow-origin: https://plate.acara.app
vary: Origin, Accept-Encoding
```

### ✅ After (browser):
Browser now sends `Origin` header automatically (because of `crossorigin="anonymous"`):
```
Request Headers:
  Origin: https://plate.acara.app

Response Headers:
  access-control-allow-origin: https://plate.acara.app
  vary: Origin, Accept-Encoding
```

## Troubleshooting

### Still seeing CORS errors?
1. **Hard refresh** - Press `Cmd + Shift + R` again
2. **Check HTML source** - Look for `<script crossorigin="anonymous"` in page source
3. **Verify deployment** - Check if latest code is deployed
4. **Check Cloudflare cache** - Manually purge: `bash scripts/purge-cloudflare-cache.sh`

### How to verify crossorigin attribute is present?
1. Visit https://plate.acara.app/login
2. View page source (`Cmd + Option + U`)
3. Search for `crossorigin`
4. Should see: `<script type="module" src="https://build-plate-assets.acara.app/build/assets/app-DFUujnb9.js" crossorigin="anonymous"></script>`

### Testing locally?
The crossorigin attribute works in both local and production environments, so you can test locally first if needed.

## Why This Fix Works

**The Technical Explanation:**

1. **Without `crossorigin` attribute:**
   - Browser treats script as "same-origin" request
   - Doesn't send `Origin` header
   - R2 sees no `Origin` header, doesn't return CORS headers
   - Browser tries to use the script anyway
   - Browser checks for CORS headers, finds none
   - ❌ CORS error: "No 'Access-Control-Allow-Origin' header is present"

2. **With `crossorigin="anonymous"` attribute:**
   - Browser treats script as CORS request
   - Sends `Origin: https://plate.acara.app` header
   - R2 sees `Origin` header, matches against CORS policy
   - R2 returns `access-control-allow-origin: https://plate.acara.app`
   - Browser receives and validates CORS headers
   - ✅ Success!

## Additional Notes

- This fix applies to all dynamically imported JavaScript modules
- Prefetch requests will also include the Origin header now
- The fix is permanent - no need to manually purge cache after future deployments (it's automatic now)
- New users never had this issue (only affected users with cached responses)

## Questions?

See full documentation:
- `docs/cors-issue-resolution.md` - Complete explanation
- `docs/cloudflare-cache-purging.md` - Cache purging setup

Test scripts:
- `scripts/verify-cors.sh` - Verify CORS configuration
- `scripts/purge-cloudflare-cache.sh` - Manually purge cache

---

**Ready to deploy?** Run step 2-4 above! 🚀
