#!/bin/bash

echo "🔍 Verifying CORS Configuration for R2 Assets"
echo "=============================================="
echo ""

# Test URLs
BASE_URL="https://build-plate-assets.acara.app"
ORIGIN="https://plate.acara.app"

# Sample asset paths to test
ASSETS=(
    "/build/assets/app-CwqsH7kj.js"
    "/build/assets/app-D4Prw1h7.css"
    "/build/manifest.json"
)

echo "Testing CORS headers with Origin: $ORIGIN"
echo ""

for asset in "${ASSETS[@]}"; do
    echo "Testing: $BASE_URL$asset"
    echo "----------------------------------------"
    
    response=$(curl -s -I "$BASE_URL$asset" -H "Origin: $ORIGIN")
    
    # Check for CORS headers
    cors_allow_origin=$(echo "$response" | grep -i "access-control-allow-origin" | tr -d '\r')
    cors_expose_headers=$(echo "$response" | grep -i "access-control-expose-headers" | tr -d '\r')
    vary_header=$(echo "$response" | grep -i "^vary:" | tr -d '\r')
    http_status=$(echo "$response" | grep -i "^HTTP/" | tr -d '\r')
    
    echo "Status: $http_status"
    
    if [ -n "$cors_allow_origin" ]; then
        echo "✅ $cors_allow_origin"
    else
        echo "❌ Missing: access-control-allow-origin"
    fi
    
    if [ -n "$vary_header" ]; then
        echo "✅ $vary_header"
    else
        echo "⚠️  Missing: Vary header"
    fi
    
    if [ -n "$cors_expose_headers" ]; then
        echo "✅ $cors_expose_headers"
    else
        echo "⚠️  Missing: access-control-expose-headers"
    fi
    
    echo ""
done

echo "=============================================="
echo "Testing OPTIONS preflight request"
echo "=============================================="
echo ""

preflight_response=$(curl -s -I -X OPTIONS "$BASE_URL/build/assets/app-CwqsH7kj.js" \
    -H "Origin: $ORIGIN" \
    -H "Access-Control-Request-Method: GET" \
    -H "Access-Control-Request-Headers: content-type")

echo "$preflight_response" | grep -i "access-control\|http/"
echo ""

echo "✅ CORS verification complete!"
echo ""
echo "If you see the access-control-allow-origin headers above,"
echo "the CORS configuration is working correctly."
echo ""
echo "If you're still experiencing issues in the browser:"
echo "1. Clear browser cache completely"
echo "2. Try: Cmd+Shift+R (macOS) or Ctrl+Shift+R (Windows)"
echo "3. Open DevTools, right-click refresh, 'Empty Cache and Hard Reload'"
echo "4. Check browser console for the exact error message"
