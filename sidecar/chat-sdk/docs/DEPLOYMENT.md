# Deployment — Cloudflare Workers

Production deploy target: Cloudflare Workers. This doc assumes you have a
Cloudflare account and an `account_id` handy. The free plan is sufficient for
a POC; KV reads/writes fit in its free daily allowance.

## One-time account setup

```bash
cd sidecar/chat-sdk
npx wrangler login                  # opens browser
npx wrangler whoami                 # confirm account
```

## KV namespace

The sidecar needs one KV namespace for the link cache. Create production and
preview namespaces, then drop the IDs into `wrangler.toml`:

```bash
npx wrangler kv namespace create SIDECAR_USER_CACHE
npx wrangler kv namespace create SIDECAR_USER_CACHE --preview
```

Each command prints a namespace id. Edit `wrangler.toml`:

```toml
[[kv_namespaces]]
binding = "SIDECAR_USER_CACHE"
id = "<production id>"
preview_id = "<preview id>"
```

## Secrets

Set the shared HMAC secret. It must match the `SIDECAR_HMAC_SECRET` in Plate's
production `.env`:

```bash
# Generate a strong secret once:
openssl rand -hex 32

npx wrangler secret put SIDECAR_HMAC_SECRET
# paste the value when prompted
```

Non-secret vars (PLATE_APP_URL, LOG_LEVEL) live under `[vars]` in
`wrangler.toml`. Update them before deploy:

```toml
[vars]
PLATE_APP_URL = "https://plate.acara.app"
LOG_LEVEL = "info"
```

## Deploy

```bash
npm test                        # green locally before shipping
npm run typecheck
npx wrangler deploy
```

Wrangler prints the deployed URL, e.g. `https://acara-chat-sdk.<your-subdomain>.workers.dev`.

Verify:

```bash
curl -s https://acara-chat-sdk.<your-subdomain>.workers.dev/healthz
# {"status":"ok"}
```

## Custom domain (optional but recommended)

Workers at `*.workers.dev` URLs are fine for testing but platforms like
WhatsApp require verified domains. Bind a custom subdomain via the Cloudflare
dashboard:

1. Cloudflare dashboard → Workers & Pages → your worker → Settings →
   **Triggers** → Add Custom Domain.
2. Set `chat-sdk.acara.app` (or similar) and save. DNS records are created
   automatically.
3. Platform webhooks now point at `https://chat-sdk.acara.app/webhooks/:platform`.

## Secret rotation

When you rotate `SIDECAR_HMAC_SECRET`:

1. Update Plate's `.env` on production to the new value (deploy if needed).
2. `npx wrangler secret put SIDECAR_HMAC_SECRET` in the sidecar.
3. Redeploy the sidecar: `npx wrangler deploy`.

There is a brief window where in-flight requests may see signature mismatches
(<1s typically). For the POC this is acceptable; if you need zero-downtime
rotation later, add a `SIDECAR_HMAC_SECRET_PREVIOUS` and have the middleware
accept either.

## Logs

Observability is enabled in `wrangler.toml`
(`[observability] enabled = true`). Tail live logs:

```bash
npx wrangler tail
```

Or view in the Cloudflare dashboard → Workers → your worker → **Logs**.

## Multiple environments (optional)

For staging:

```toml
[env.staging]
name = "acara-chat-sdk-staging"
vars = { LOG_LEVEL = "debug", PLATE_APP_URL = "https://plate-staging.acara.app" }

[[env.staging.kv_namespaces]]
binding = "SIDECAR_USER_CACHE"
id = "<staging kv id>"
```

Deploy with `npx wrangler deploy --env staging`. Secrets are set per-env:
`npx wrangler secret put SIDECAR_HMAC_SECRET --env staging`.

## Rollback

Cloudflare keeps the last few deployments. From the dashboard:
Workers → your worker → **Deployments** → pick a previous version → Rollback.

Or via CLI:

```bash
npx wrangler rollback --message "reverting to <reason>"
```

## Checklist before going live

- [ ] `SIDECAR_HMAC_SECRET` matches between Plate and sidecar
- [ ] KV namespace ids are filled in `wrangler.toml` (not `REPLACE_WITH_…`)
- [ ] `PLATE_APP_URL` points at production Plate
- [ ] `LOG_LEVEL` = `info` (not `debug`, which exposes `/debug/last-reply/*`)
- [ ] `php artisan migrate` has run on Plate for `user_chat_platform_links`
- [ ] `npx wrangler tail` shows healthy `/healthz` pings after deploy
