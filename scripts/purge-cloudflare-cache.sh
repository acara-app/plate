#!/bin/bash

# Purge Cloudflare Cache for R2 Asset Bucket
# This script purges the cache for build-plate-assets.acara.app after deploying new assets

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🔄 Purging Cloudflare Cache for Asset Bucket${NC}"
echo "=============================================="
echo ""

# Check if required environment variables are set
if [ -z "$CLOUDFLARE_API_TOKEN" ] && [ -z "$CLOUDFLARE_API_KEY" ]; then
    echo -e "${YELLOW}⚠️  Cloudflare API credentials not found.${NC}"
    echo ""
    echo "To enable automatic cache purging, add one of the following to your .env:"
    echo ""
    echo "Option 1 (Recommended - API Token):"
    echo "  CLOUDFLARE_API_TOKEN=your_api_token_here"
    echo "  CLOUDFLARE_ZONE_ID=your_zone_id_here"
    echo ""
    echo "Option 2 (Legacy - API Key):"
    echo "  CLOUDFLARE_API_KEY=your_global_api_key"
    echo "  CLOUDFLARE_EMAIL=your_cloudflare_email"
    echo "  CLOUDFLARE_ZONE_ID=your_zone_id_here"
    echo ""
    echo "To get your Zone ID:"
    echo "  1. Go to Cloudflare Dashboard"
    echo "  2. Select your domain (build-plate-assets.acara.app)"
    echo "  3. Scroll down to 'API' section on the right"
    echo "  4. Copy the 'Zone ID'"
    echo ""
    echo -e "${YELLOW}Skipping cache purge...${NC}"
    exit 0
fi

if [ -z "$CLOUDFLARE_ZONE_ID" ]; then
    echo -e "${RED}❌ CLOUDFLARE_ZONE_ID is required but not set.${NC}"
    echo "Please add CLOUDFLARE_ZONE_ID to your .env file."
    exit 1
fi

# Determine which authentication method to use
if [ -n "$CLOUDFLARE_API_TOKEN" ]; then
    AUTH_HEADER="Authorization: Bearer $CLOUDFLARE_API_TOKEN"
    echo "Using API Token authentication"
elif [ -n "$CLOUDFLARE_API_KEY" ] && [ -n "$CLOUDFLARE_EMAIL" ]; then
    AUTH_HEADER="X-Auth-Key: $CLOUDFLARE_API_KEY"
    EMAIL_HEADER="X-Auth-Email: $CLOUDFLARE_EMAIL"
    echo "Using API Key authentication"
else
    echo -e "${RED}❌ Invalid Cloudflare credentials configuration.${NC}"
    exit 1
fi

echo "Zone ID: $CLOUDFLARE_ZONE_ID"
echo ""

# Purge all cache for the zone
echo "Purging cache..."

if [ -n "$EMAIL_HEADER" ]; then
    response=$(curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/purge_cache" \
        -H "$AUTH_HEADER" \
        -H "$EMAIL_HEADER" \
        -H "Content-Type: application/json" \
        --data '{"purge_everything":true}')
else
    response=$(curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/purge_cache" \
        -H "$AUTH_HEADER" \
        -H "Content-Type: application/json" \
        --data '{"purge_everything":true}')
fi

# Check if the request was successful
success=$(echo "$response" | grep -o '"success":true' || echo "")

if [ -n "$success" ]; then
    echo -e "${GREEN}✅ Cache purged successfully!${NC}"
    echo ""
    echo "The Cloudflare cache has been cleared for build-plate-assets.acara.app"
    echo "New assets should now be immediately available to users."
else
    echo -e "${RED}❌ Failed to purge cache.${NC}"
    echo ""
    echo "Response from Cloudflare:"
    echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
    exit 1
fi

echo ""
echo "=============================================="
echo -e "${GREEN}✅ Cache purge complete!${NC}"
