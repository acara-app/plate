# Acara Plate Messaging Sidecar

`sidecar/chat-sdk` is a Cloudflare Workers service that receives webhooks from
messaging platforms (Discord, WhatsApp, Slack, iMessage, …), forwards the
message to the Plate Laravel advisor over a signed HTTP API, and sends the
reply back on the originating platform.

The Plate app remains the system of record for users, account linking,
advisor generation, memory, and credit accounting. The sidecar only handles
per-platform I/O.

```text
webhook → verify signature → Plate /api/v2/messaging/.../chat-turns → reply
```

**Ships with:** a single `PlatformAdapter` interface and a `mock` adapter used
for tests and local demos. Real adapters (Discord, WhatsApp, Slack, …) plug
into the same interface — see [docs/ADDING_A_PLATFORM.md](./docs/ADDING_A_PLATFORM.md).

Plate's existing Telegram integration is unchanged; the sidecar is for _new_
platforms.

## Requirements

- Node.js 22+, npm 10+
- A Cloudflare account (free tier is fine)
- Plate running locally (Herd → `http://plate.test`) or reachable over HTTPS

## Quickstart

```bash
cd sidecar/chat-sdk
cp .dev.vars.example .dev.vars
# edit .dev.vars: set SIDECAR_HMAC_SECRET to match Plate's .env

npm install
npm test                # Vitest, runs in the Workers runtime via miniflare
npm run dev             # wrangler dev → http://127.0.0.1:8787
```

On the Plate side:

```bash
# in the Plate root:
#   1. add SIDECAR_HMAC_SECRET=<same value> to .env
#   2. php artisan migrate
```

Smoke test end-to-end with the mock adapter:

```bash
bash docs/curl-examples.sh
```

## Documentation

- **[ARCHITECTURE.md](./docs/ARCHITECTURE.md)** — flow diagram, the
  `PlatformAdapter` interface, decision log.
- **[LOCAL_DEV.md](./docs/LOCAL_DEV.md)** — full local setup, signing requests
  by hand, tunnels for live platform webhooks.
- **[DEPLOYMENT.md](./docs/DEPLOYMENT.md)** — Cloudflare Workers deployment:
  KV namespaces, secrets, custom domains, secret rotation.
- **[ADDING_A_PLATFORM.md](./docs/ADDING_A_PLATFORM.md)** — step-by-step for a
  new adapter (Discord walked as a worked example).

## Layout

```
src/
  index.ts              Hono app + Workers entry
  env.ts                Zod schema for env + Workers bindings
  routes/               /healthz, /webhooks/:platform, /debug/*
  platforms/            PlatformAdapter interface + registry + mock adapter
  plate/                HMAC-signed client for Plate's /api/v2/messaging/*
  services/             KV link cache
  lib/                  HMAC, structured logger
tests/                  Vitest suite
docs/                   Deep-dive docs
wrangler.toml           Cloudflare Workers config
vitest.config.ts        @cloudflare/vitest-pool-workers
```

## Scripts

| Command | Purpose |
| --- | --- |
| `npm run dev` | `wrangler dev`, local Workers runtime at `http://127.0.0.1:8787` |
| `npm test` | Run the Vitest suite once |
| `npm run test:watch` | Vitest in watch mode |
| `npm run typecheck` | `tsc --noEmit` |
| `npm run deploy` | `wrangler deploy` to Cloudflare |
