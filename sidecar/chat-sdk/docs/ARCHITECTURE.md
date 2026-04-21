# Architecture

## Scope

The sidecar is a thin I/O translator between messaging platforms and Plate.
It does **not** generate AI replies, track users, or store conversation
history. It owns:

- Webhook reception and per-platform signature verification.
- Parsing platform payloads into a platform-neutral `InternalMessage`.
- Calling Plate's RESTful `/api/v2/messaging/*` API over HMAC-signed HTTP.
- Formatting Plate's reply for the originating platform and sending it.
- A 24-hour KV cache of `platform_user_id → plate_user_id` to skip the link
  resolution round-trip for warm users.

## End-to-end flow

```text
┌──────────────┐   POST /webhooks/:platform   ┌────────────────────────┐
│   Platform   │ ───────────────────────────▶ │ Sidecar (Workers/Hono) │
└──────────────┘                              └────────────────────────┘
                                                 │ adapter.receive()
                                                 │   └─ verify + parse
                                                 │      → InternalMessage
                                                 │
                             HMAC POST           ▼
         ┌────────────────────────────────────────────────────────┐
         │  Plate: VerifySidecarSignature middleware              │
         │   → POST /api/v2/messaging/platforms/{p}/users/{u}/chat-turns │
         │   → ChatTurnsController::store                         │
         │   → UserChatPlatformLink lookup                        │
         │   → unlinked: IssueLinkingCodeAction → 409 Conflict    │
         │   → linked:   DispatchChatTurnAction  → 201 Created    │
         │                  → ProcessesAdvisorMessage::handle     │
         └──────────────────────┬─────────────────────────────────┘
                                │
                                ▼
                        adapter.send(reply)
                                │
                                ▼
                            Platform UI
```

## Internal message shape

Modelled loosely on Vercel AI SDK's `UIMessage` so adapters, tests, and future
streaming/web chat can converge on the same structure.

```ts
type InternalMessagePart =
  | { type: 'text'; text: string }
  | { type: 'image'; url: string; mediaType?: string }
  | { type: 'file'; url: string; mediaType?: string; name?: string }

interface InternalMessage {
  id: string
  role: 'user'
  platform: string            // = adapter.name
  platformUserId: string      // e.g. Discord user id
  platformMessageId?: string  // for in-thread replies
  platformChatId?: string     // DM vs channel targeting
  parts: InternalMessagePart[]
  receivedAt: number          // epoch ms
}
```

## PlatformAdapter interface

```ts
interface PlatformAdapter {
  readonly name: string
  receive(c: Context<HonoEnv>): Promise<ReceiveResult>
  send(c: Context<HonoEnv>, reply: OutgoingReply): Promise<void>
  describeLinking(code: string): string
}

type ReceiveResult =
  | { kind: 'ok'; message: InternalMessage }
  | { kind: 'invalid_signature' }
  | { kind: 'ignored'; reason?: string }
```

`receive` intentionally combines signature verification and parsing. Platforms
like Meta/WhatsApp compute HMAC over the raw request body; reading the body
once and handing back either a parsed message or a typed failure is cleaner
than doing `verify` → `parse` as two separate trips through the body.

## Plate HTTP API (RESTful)

All sidecar → Plate traffic goes through one resource scoped by
`(platform, platformUserId)` — always in the URL path, never in the body.

| Verb | Path | Success | Failure |
| --- | --- | --- | --- |
| `POST` | `/api/v2/messaging/platforms/{platform}/users/{platformUserId}/chat-turns` | `201 { plate_user_id, conversation_id, response }` | `409 { linking_code, expires_at }` when unlinked |

`409 Conflict` means "the platform user is known but not linked to a Plate
account yet" — the sidecar prompts the user with the linking code in the same
round trip. The HTTP status code itself carries the outcome; there is no
separate `status` discriminator in response bodies.

Request body: `{ message, platform_message_id?, attachments? }`.

Platform-user lookup and standalone linking-code issuance are deliberately
omitted — they were speculative endpoints that the webhook flow never needed.
`POST .../chat-turns` handles both linked and unlinked users in one call. If a
future adapter gains a `/whoami` or `/relink` command, add the resource then.

## Request signing to Plate

Every sidecar → Plate request carries two headers:

| Header | Value |
| --- | --- |
| `X-Sidecar-Timestamp` | Current unix seconds |
| `X-Sidecar-Signature` | `HMAC-SHA256(SIDECAR_HMAC_SECRET, "<timestamp>.<raw-body>")` as lowercase hex |

`VerifySidecarSignature` on the Plate side rejects:

- Missing / non-numeric timestamp → `missing_signature`
- Clock skew greater than `SIDECAR_CLOCK_SKEW_SECONDS` (default 60) → `stale_timestamp`
- Signature mismatch → `invalid_signature`

Both sides share `SIDECAR_HMAC_SECRET`. No per-user auth token flows through
the sidecar; it is a symmetric machine-to-machine transport.

## KV cache

`SIDECAR_USER_CACHE` stores two kinds of values:

- `link:<platform>:<platform_user_id>` → `{ plateUserId, linkedAt }`, 24h TTL.
- `mock:last-reply:<platform_user_id>` → last reply sent by the mock adapter,
  5 min TTL, used only by `/debug/last-reply/mock/:user` for local smoke tests.

## Decision log

- **Cloudflare Workers, not Node/Docker.** Per-webhook CPU is tiny; Workers
  give us no cold start at scale, KV, and a single deploy story.
- **Hono, not itty-router or raw `fetch`.** Small enough for Workers' script
  limits, strong typed route/middleware ergonomics, and a multi-runtime escape
  hatch if we ever need Node.
- **HMAC, not Sanctum.** The sidecar is server-to-server; a symmetric shared
  secret is sufficient and matches the pattern Stripe/Slack/GitHub use for
  their own webhooks.
- **Polymorphic `user_chat_platform_links` table.** Telegram keeps its
  `user_telegram_chats` table (with its Telegraph FK); the new table is
  sidecar-owned and platform-agnostic.
- **No Vercel AI SDK dependency (yet).** We borrow the `UIMessage` shape as
  inspiration only. If the sidecar ever needs to generate or transform AI
  output itself, add it then.

## Why not migrate Telegram?

Telegram uses `defstudio/telegraph`, which depends on Plate's Eloquent models
(`TelegraphChat`) and already has tests. Moving it to the sidecar would force
a re-implementation of chat-id resolution and give up existing coverage. The
sidecar is the path for _new_ platforms; Telegram stays where it is unless
there is a concrete reason to move it.
