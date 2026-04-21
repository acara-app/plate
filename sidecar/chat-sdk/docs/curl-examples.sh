#!/usr/bin/env bash
# End-to-end smoke tests for the messaging sidecar.
#
# Usage:
#   bash docs/curl-examples.sh
#
# Env vars (with defaults):
#   SIDECAR_URL       base URL of the sidecar (default: http://127.0.0.1:8787)
#   PLATE_URL         base URL of Plate       (default: http://plate.test)
#   SIDECAR_SECRET    HMAC secret             (default: value from .dev.vars)
#
# Prerequisites:
#   - sidecar is running:   npm run dev
#   - Plate is migrated with SIDECAR_HMAC_SECRET set in its .env
#   - Both secrets match

set -euo pipefail

SIDECAR_URL="${SIDECAR_URL:-http://127.0.0.1:8787}"
PLATE_URL="${PLATE_URL:-http://plate.test}"

if [[ -z "${SIDECAR_SECRET:-}" ]]; then
  if [[ -f .dev.vars ]]; then
    # shellcheck disable=SC2016
    SIDECAR_SECRET=$(awk -F= '/^SIDECAR_HMAC_SECRET=/ {gsub(/"/,"",$2); print $2; exit}' .dev.vars)
  fi
fi

if [[ -z "${SIDECAR_SECRET:-}" ]]; then
  echo "SIDECAR_SECRET is not set and .dev.vars does not contain SIDECAR_HMAC_SECRET" >&2
  exit 1
fi

sign() {
  # sign <raw-body> → prints "<timestamp> <signature>"
  local body="$1"
  local ts
  ts=$(date +%s)
  local sig
  sig=$(printf '%s.%s' "$ts" "$body" | openssl dgst -sha256 -hmac "$SIDECAR_SECRET" | awk '{print $2}')
  printf '%s %s' "$ts" "$sig"
}

step() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }

step "Sidecar health"
curl -fsS "$SIDECAR_URL/healthz"; echo
curl -fsS "$SIDECAR_URL/" ; echo

step "Mock webhook (unlinked user — expect link_required)"
curl -fsS -X POST "$SIDECAR_URL/webhooks/mock" \
  -H 'content-type: application/json' \
  -d '{"from":"alice","text":"hello advisor"}'
echo

step "Mock adapter's last reply (from KV)"
# Only works when LOG_LEVEL=debug in .dev.vars
curl -fsS "$SIDECAR_URL/debug/last-reply/mock/alice" || echo "(debug route disabled; set LOG_LEVEL=debug in .dev.vars)"
echo

PLATFORM="mock"
USER="alice"
CHAT_TURNS_PATH="/api/v2/messaging/platforms/$PLATFORM/users/$USER/chat-turns"

step "Direct chat-turn POST (signed) — expect 409 + linking_code for a fresh user"
body='{"message":"hi direct"}'
read -r ts sig <<< "$(sign "$body")"
curl -isS -X POST "$PLATE_URL$CHAT_TURNS_PATH" \
  -H 'content-type: application/json' \
  -H "x-sidecar-timestamp: $ts" \
  -H "x-sidecar-signature: $sig" \
  -d "$body" | head -20
echo
