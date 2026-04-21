# Adding a Platform Adapter

The sidecar ships with one adapter (`mock`) and an interface. To add a real
platform, implement that interface, register it, and write tests. This doc
walks through **Discord** as a concrete example. WhatsApp, Slack, iMessage,
etc. follow the same shape with different verification schemes and send APIs.

## The interface

See `src/platforms/types.ts`:

```ts
interface PlatformAdapter {
  readonly name: string
  receive(c: Context<HonoEnv>): Promise<ReceiveResult>
  send(c: Context<HonoEnv>, reply: OutgoingReply): Promise<void>
  describeLinking(code: string): string
}
```

Your adapter owns:

- Reading the raw request body.
- Verifying the platform's signature scheme.
- Parsing the payload into an `InternalMessage` (text, optional attachments).
- Sending a reply via the platform's outbound API.
- Writing a friendly linking instruction containing the 8-char code.

## Step 1 — create the adapter folder

```
src/platforms/discord/
  adapter.ts
  verify.ts
```

## Step 2 — implement verify.ts

Discord signs with Ed25519. You'll need `@noble/ed25519` (or equivalent; avoid
Node's `crypto` — Workers runs against Web Crypto):

```ts
import { verifyAsync } from '@noble/ed25519'

export async function verifyDiscord(
  publicKeyHex: string,
  timestamp: string,
  rawBody: string,
  signatureHex: string,
): Promise<boolean> {
  const message = new TextEncoder().encode(timestamp + rawBody)
  return verifyAsync(hex(signatureHex), message, hex(publicKeyHex))
}
```

## Step 3 — implement adapter.ts

```ts
import type { Context } from 'hono'
import type { HonoEnv } from '../../env'
import type { OutgoingReply, PlatformAdapter, ReceiveResult } from '../types'
import { verifyDiscord } from './verify'

export const discordAdapter: PlatformAdapter = {
  name: 'discord',

  async receive(c: Context<HonoEnv>): Promise<ReceiveResult> {
    const signature = c.req.header('x-signature-ed25519') ?? ''
    const timestamp = c.req.header('x-signature-timestamp') ?? ''
    const rawBody = await c.req.text()

    const publicKey = c.env.DISCORD_PUBLIC_KEY ?? ''
    const ok = await verifyDiscord(publicKey, timestamp, rawBody, signature)
    if (!ok) return { kind: 'invalid_signature' }

    const payload = safeJson(rawBody)
    if (!payload || payload.type !== 2 /* APPLICATION_COMMAND */) {
      return { kind: 'ignored', reason: 'unsupported_interaction_type' }
    }

    // ... map Discord interaction → InternalMessage
    return {
      kind: 'ok',
      message: {
        id: payload.id,
        role: 'user',
        platform: 'discord',
        platformUserId: payload.member?.user?.id ?? payload.user?.id,
        platformChatId: payload.channel_id,
        parts: [{ type: 'text', text: payload.data.options[0].value }],
        receivedAt: Date.now(),
      },
    }
  },

  async send(c, reply) {
    await fetch(`https://discord.com/api/v10/channels/${reply.platformChatId}/messages`, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        authorization: `Bot ${c.env.DISCORD_BOT_TOKEN}`,
      },
      body: JSON.stringify({ content: reply.text }),
    })
  },

  describeLinking(code) {
    return `Link your Plate account: open Plate → Settings → Integrations and paste **${code}**`
  },
}

function safeJson(raw: string): any {
  try { return JSON.parse(raw) } catch { return null }
}
```

## Step 4 — register it

Edit `src/platforms/registry.ts`:

```ts
import { discordAdapter } from './discord/adapter'
import { mockAdapter } from './mock/adapter'

const adapters: Record<string, PlatformAdapter> = {
  [mockAdapter.name]: mockAdapter,
  [discordAdapter.name]: discordAdapter,
}
```

## Step 5 — add env vars

- `src/env.ts`: add to the Zod schema:
  ```ts
  DISCORD_PUBLIC_KEY: z.string(),
  DISCORD_BOT_TOKEN: z.string(),
  ```
- `.dev.vars.example` and `wrangler.toml [vars]`: add non-secret placeholders.
- Set secrets in production:
  ```bash
  npx wrangler secret put DISCORD_PUBLIC_KEY
  npx wrangler secret put DISCORD_BOT_TOKEN
  ```

## Step 6 — tests

Add `tests/discord-adapter.test.ts`:

- Signature verification pass and fail cases (pre-compute a known signature).
- Ignored interaction types.
- Happy-path parse → `InternalMessage`.
- `send()` calls the Discord REST endpoint with the right path + bearer.

Use `vi.spyOn(globalThis, 'fetch')` to mock outbound calls; `tests/webhooks.test.ts` already does this for Plate and is a good template.

## Step 7 — point the platform at the sidecar

Deploy (`npx wrangler deploy`) or tunnel (`cloudflared tunnel --url …`), then
configure the platform's webhook URL:

```
POST https://chat-sdk.acara.app/webhooks/discord
```

## Checklist

- [ ] Signature verification tested with a known-good and known-bad payload.
- [ ] Unsupported payload types return `{ kind: 'ignored' }`, not 500.
- [ ] `send()` handles the platform's rate-limit and retry semantics.
- [ ] `describeLinking()` mentions the actual navigation path a user should
      take in the Plate web UI.
- [ ] Secrets are set via `wrangler secret put`, not committed.
- [ ] New adapter is listed in `listAdapters()` output (visible at `GET /`).
