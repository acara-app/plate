# Local Development

## Prerequisites

- Node 22+ and npm 10+
- Laravel Herd (or any way to reach Plate at a stable hostname)
- Plate running locally, migrated, with a known `SIDECAR_HMAC_SECRET`

## One-time setup

```bash
# Plate side
cd /Users/tuvshinjargal/Herd/plate
echo 'SIDECAR_HMAC_SECRET="dev-sidecar-secret-keep-this-long-enough"' >> .env
php artisan config:clear
php artisan migrate

# Sidecar side
cd sidecar/chat-sdk
cp .dev.vars.example .dev.vars
# edit .dev.vars:
#   SIDECAR_HMAC_SECRET="dev-sidecar-secret-keep-this-long-enough"
#   PLATE_APP_URL="http://plate.test"
#   LOG_LEVEL="debug"
npm install
```

Verify:

```bash
npm test                 # all Vitest tests should pass
npm run typecheck
```

## Running the sidecar

```bash
npm run dev
# → ⎔ Starting local server...
# → [wrangler:info] Ready on http://127.0.0.1:8787
```

Probe it:

```bash
curl -s http://127.0.0.1:8787/          # adapters: ["mock"], status: ok
curl -s http://127.0.0.1:8787/healthz   # status: ok
```

## Sending a mock message end-to-end

The mock adapter accepts raw JSON with `{ from, text }` — no signature
verification. This lets you exercise the full Plate round trip without needing
Discord/WhatsApp credentials.

```bash
curl -sS -X POST http://127.0.0.1:8787/webhooks/mock \
  -H 'content-type: application/json' \
  -d '{"from":"alice","text":"hello advisor"}'
```

Expected:

- First call: `{"status":"link_required"}`. The reply with the linking code is
  stored in KV — fetch it back with `/debug/last-reply/mock/alice` (only
  exposed when `LOG_LEVEL=debug`).
- Look up the issued code in Plate (`select * from user_chat_platform_links`)
  and link it to a user (see _Linking a user_ below).
- Next call with the same `from` will dispatch the advisor and return
  `{"status":"ok", "conversation_id": "..."}`.

## Linking a user (manual, for local dev)

Until a web UI ships, you can link the mock user directly via tinker:

```bash
cd /Users/tuvshinjargal/Herd/plate
php artisan tinker --execute 'App\Models\UserChatPlatformLink::forUser("mock", "alice")->firstOrFail()->markAsLinked(App\Models\User::first());'
```

## Signing a request by hand

The HMAC is computed over `"<timestamp>.<raw-body>"`. Sign the exact body the
request will carry.

```bash
SECRET="dev-sidecar-secret-keep-this-long-enough"
PLATFORM="mock"
USER="alice"
BODY='{"message":"hello"}'
TS=$(date +%s)
SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

curl -sS -X POST "http://plate.test/api/v2/messaging/platforms/$PLATFORM/users/$USER/chat-turns" \
  -H "content-type: application/json" \
  -H "x-sidecar-timestamp: $TS" \
  -H "x-sidecar-signature: $SIG" \
  -d "$BODY"
```

`docs/curl-examples.sh` bundles this up.

## Tunnelling real platform webhooks

When you have a real Discord/WhatsApp/Slack webhook configured to point at
your local sidecar:

```bash
# Quick tunnel:
npx wrangler dev --ip 0.0.0.0
# In a second shell:
cloudflared tunnel --url http://localhost:8787
```

Cloudflare Tunnel will print an HTTPS URL. Paste it into the platform's
webhook configuration.

## Common issues

- **`invalid_signature` from Plate.** Secrets don't match — compare Plate's
  `.env` and the sidecar's `.dev.vars` byte for byte. No trailing newline in
  either.
- **`stale_timestamp`.** Clock skew between your machine and Plate's server is
  greater than 60s. Either fix the clock or bump `SIDECAR_CLOCK_SKEW_SECONDS`.
- **`unknown_platform` (404).** You called `/webhooks/<name>` for an adapter
  that isn't registered in `src/platforms/registry.ts`.
- **Hot reload not picking up changes.** `wrangler dev` watches `src/**` by
  default; restart if you edit `wrangler.toml` or `package.json`.
- **`cloudflare:test` module not found.** Ensure `tests/env.d.ts` exists; it
  references the Vitest pool's ambient declarations.
