# Durable, Resumable AI Streaming

> Status: **Phases 1–5 ✅ shipped.** The durable resumable AI stream is complete end-to-end.
>
> **Phase 5 (hardening):** `streams:prune-expired` command (scheduled hourly) deletes runs+chunks past
> `expires_at`; `StreamAgentRunJob` now coalesces consecutive text deltas into one chunk row
> (`altani.stream.coalesce_text_deltas`, default true) to cut write amplification, captures
> `assistant_message_id` on completion, and on hard failure best-effort persists the user prompt + partial
> answer (`meta.partial=true`, `usage=[]` so no double-charge) with an airtight dedup guard. Reasoning
> deltas stay raw (text is the dominant volume); the stalled-run tail guard shipped in Phase 2.
>
> **Mobile adoption:** the v2 `show` endpoint now also returns `active_stream` ({run_id, prompt} | null)
> so the iOS app (acara-health-sync) can resume on conversation open via the existing GET resume
> endpoint. The iOS client adopted resume and refactored its stream parsing onto SwiftAISDK's
> `readUIMessageStream` (UIMessage snapshots) — see that repo.
> Scope: backend + frontend rewrite of the AI chat streaming path so long-running generations are
> **queue-driven, persisted as chunks, and resumable** across disconnects/reloads/worker restarts.
> Testing is intentionally out of scope for this doc (per request); a minimal manual verification
> checklist is included at the end.
>
> **Phase 2 note:** generation now runs in `StreamAgentRunJob` (the sole producer); the POST returns
> an SSE tail (`ReplayAgentStreamAction`) over the ledger. Each chunk also stores the vendor-computed
> `toVercelProtocolArray()` (new `vercel` column) so replay re-emits the exact wire form with zero
> re-derivation. Requires a running queue worker in production.
>
> **Phase 3 note:** added GET resume endpoints — `chat.stream.resume`
> (`chat/stream/{conversation}/runs/{run}/resume`) and `api.v2.chat.stream.resume` — that reuse
> `ReplayAgentStreamAction` (`?from={sequence}` cursor). `ReplayAgentStreamAction::isResumable()`
> drives `204 No Content` (the SDK reads 204 as "no active stream" → client falls back to history)
> when a run is expired or finished with nothing newer than the cursor. Backend-only; no frontend
> change yet (Phase 4 wires `useChat({ resume })`).
>
> **Phase 4 note:** the web chat now resumes a mid-generation reload. `ChatController@create` exposes
> an eager `activeStream` prop (`{run_id, prompt}`) for the latest queued/running run; `use-chat-stream.ts`
> sets `resume: true` + `prepareReconnectToStreamRequest` (→ `chat.stream.resume.url`) and seeds the
> in-flight user message (the run carries `prompt`, since the SDK only persists the user message when
> the turn completes). Web-only; the API v2 resume endpoint stays available for the mobile app to adopt.
> Known minor edges (Phase 5): image attachments aren't re-seeded on reload, and a sub-millisecond
> race (run completing between the eager `activeStream` read and the deferred `messages` resolution)
> could momentarily duplicate the in-flight message.

## Context — why this change

Today a chat turn is streamed **synchronously from the web worker**:

- `ChatController@stream` (web) and `Api\V2\ChatController@stream` (mobile) return
  `Laravel\Ai\Responses\StreamableAgentResponse` directly. The PHP worker holds the live LLM
  stream for up to `#[Timeout(120)]` (`app/Ai/Agents/AgentRunner.php`).
- The assistant **and** user messages are persisted **only after the full stream completes
  server-side**: the SDK's `RememberConversation` middleware
  (`vendor/laravel/ai/src/Middleware/RememberConversation.php`) registers a `->then()` callback that
  fires at the end of `StreamableAgentResponse::getIterator()`. The callback writes both rows to
  `agent_conversation_messages` via `DatabaseConversationStore`.

This is fragile in three concrete ways:

1. **Disconnect = data loss.** If the client drops mid-stream (mobile network, tab close, reload),
   server-side iteration aborts, `->then()` never runs, and **nothing is persisted** — not even the
   partial answer.
2. **No resume.** Reload or reconnect loses the in-flight generation entirely; the frontend
   (`use-chat-stream.ts`) re-hydrates only finalized history.
3. **Web worker held hostage.** A 2-minute generation ties up a request worker for its full duration.

**Goal:** generation runs in a background job; every chunk is persisted as produced; an SSE endpoint
can replay persisted chunks then tail live ones, so any client reattaches seamlessly.

### Verified infrastructure constraints (these shape the design)

| Fact | Value | Source |
|---|---|---|
| Broadcast driver | `log` (no-op) | `.env.example` `BROADCAST_CONNECTION=log` |
| Reverb / Pusher / Ably | **not installed** | absent from `composer.json` |
| `laravel-echo` / `pusher-js` | **not installed** | absent from `package.json` |
| `routes/channels.php` | does not exist | — |
| Queue | `database` (worker runs in dev `composer dev`) | `config/queue.php`, `composer.json` |
| Cache | `database` | `.env.example` `CACHE_STORE=database` |
| Frontend AI SDK | `ai@6.0.195`, `@ai-sdk/react@3.0.197` | `node_modules` |

**Implication:** websocket pub/sub is unavailable, so the SDK's `BroadcastAgent` (whose
`broadcastNow()` is a no-op under `log`) cannot deliver chunks. The live tail must be a **short-interval
DB poll over an indexed ledger** — durable with **zero new infrastructure**. Reverb is a documented
*future* swap of only the tail loop (see Phase 5), not a prerequisite.

The frontend SDK already ships the resume primitives we need (currently unused):
`useChat({ resume: true })`, `DefaultChatTransport.reconnectToStream` (issues a GET, treats `204` as
"no active stream"), and `prepareReconnectToStreamRequest` (can fully override the GET URL). So the
client change is small and idiomatic — no hand-rolled `EventSource`.

## Target architecture

```
POST /chat/stream/{conversation}                 (unchanged route + body)
  └─ Gate::authorize('view', conversation)
  └─ StartAgentStreamRunAction
        ├─ EnforceAiUsageLimit  ── synchronous ── 402 usage_limit_exceeded (unchanged path)
        ├─ insert agent_stream_runs (status=queued, run_id=ULID, channel, model, prompt snapshot)
        ├─ dispatchSummarizationIfNeeded + memoryExtraction->dispatchIfEligible  (moved here)
        └─ dispatch StreamAgentRunJob (afterCommit)
  └─ return SSE tail = ReplayAgentStreamAction(runId, from=0)   ← web worker freed immediately

StreamAgentRunJob  (queued, the ONLY producer)
  ├─ Context::add('chat.channel'|'chat.conversation_id')        ← re-established in worker
  ├─ run = running
  ├─ AgentRunner->runWithConversation(payload, user, conversationId)   (unchanged)
  │     ->each(fn StreamEvent $e => StreamChunkStore::append(runId, $e))   ← persist each chunk
  │     ->then(...)   ← SDK RememberConversation writes canonical assistant message (unchanged)
  ├─ on StreamStart: copy invocation_id onto run
  ├─ on completion: copy assistant_message_id onto run; status=completed; AgentStreamed→TrackAiUsage (once)
  └─ failed(): append terminal stream_failed chunk; status=failed; best-effort partial message

GET …/runs/{run}/resume?from={seq}     (NEW, additive)
  └─ ReplayAgentStreamAction: replay chunks where sequence>from, then DB-poll tail until terminal
  └─ 204 when run unknown/expired/already-complete-with-nothing-new  → client falls back to history
```

Single producer (the job), many pure-reader consumers (the POST tail, reloaded tabs, mobile). Exactly
**one LLM call per turn**.

## Data model — two new tables

Both use **ULID** PKs where ordering matters (the SDK's `agent_conversations`/`agent_conversation_messages`
use unsortable UUID `string(36)`, so we do **not** reuse their id scheme for sequencing).

**`agent_stream_runs`** — one row per assistant turn.

| column | type | notes |
|---|---|---|
| `id` | `string(26)` PK | run_id, ULID (`HasUlids`) |
| `conversation_id` | `string(36)` index | → `agent_conversations.id` |
| `user_id` | FK | |
| `agent` | string | agent FQCN |
| `channel` | string | `web` \| `mobile` \| `telegram` |
| `model` | string | resolved model name |
| `status` | `string(20)` | `queued` \| `running` \| `completed` \| `failed` |
| `invocation_id` | string null | SDK invocationId; usage-dedup correlation |
| `assistant_message_id` | `string(36)` null | the `agent_conversation_messages` row written in `->then()` |
| `next_sequence` | unsigned int default 0 | atomic per-run counter for chunk sequencing |
| `error` | text null | |
| `expires_at` | timestamp | `now()->addMinutes(config('altani.stream.run_ttl_minutes', 30))` |
| timestamps + `finalized_at` | | |

Index: `(conversation_id, status)` to find the active run for a conversation on reconnect.

**`agent_stream_chunks`** — append-only ledger.

| column | type | notes |
|---|---|---|
| `id` | bigint auto PK | cheap insert |
| `run_id` | `string(26)` index | → runs.id |
| `sequence` | unsigned int | **`UNIQUE(run_id, sequence)`** — idempotency, no dupes |
| `type` | string | `StreamEvent::type()` (`text_delta`, `tool_call`, `tool_result`, `stream_start`, `stream_end`, `stream_failed`, …) |
| `payload` | json | **exactly `$event->toArray()`** (full envelope: id, invocation_id, message_id, delta, timestamp) |
| `created_at` | timestamp | |

Index: `(run_id, sequence)` for ordered replay-since-N.

**Why store raw `toArray()` (not `toVercelProtocolArray()`):** `toArray()` keeps `invocation_id`,
`message_id`, and `timestamp`; the Vercel form discards them. We re-derive the exact Vercel wire shape
at read time, which also keeps storage protocol-version-agnostic across SDK upgrades.

**Canonical transcript is unchanged.** The final message still lives in `agent_conversation_messages`
(written once by `RememberConversation` inside the job). The ledger is transient: a daily prune deletes
runs/chunks past `expires_at` (Phase 5). Resuming a long-completed run returns `204`, and the client
falls back to persisted history via `BuildConversationMessagesAction`.

## Backend components

New, following the repo's Action / Contract (no suffix) / `bindIf` / spatie-data conventions:

- **`app/Contracts/Streaming/StreamChunkStore.php`** (contract, no suffix):
  `append(string $runId, StreamEvent $event): int`, `chunksAfter(string $runId, int $sequence): iterable`,
  `latestSequence(string $runId): int`, `markRunStatus(string $runId, string $status): void`.
  `append()` increments `runs.next_sequence` atomically (`DB::transaction` + `lockForUpdate`) so sequence
  assignment is dense and never relies on autoincrement gaps.
- **`app/Services/Streaming/DatabaseStreamChunkStore.php`** — DB implementation. Bound via
  `AppServiceProvider::bindIf()` so a future `RedisStreamChunkStore` can drop in (consistent with the
  existing open-core memory pattern; keeps `main` community-safe).
- **`app/Jobs/StreamAgentRunJob.php`** — `ShouldQueue`, `database` connection, `tries = 1`,
  `WithoutOverlapping($runId)`, job timeout ≥ 130 (AgentRunner is `#[Timeout(120)]`). Mirrors the
  *shape* of `vendor/.../Jobs/BroadcastAgent.php` (`->each()`/`->then()`/`failed()`) **without
  subclassing or patching vendor** — we replicate ~30 lines and swap `broadcastNow()` for
  `StreamChunkStore::append()`. `failed()` always appends a terminal `stream_failed` chunk (mirrors
  `BroadcastAgent::failed`) and, if any `text_delta` chunks exist, best-effort persists a partial
  assistant message so a reload shows the partial answer.
- **`app/Actions/StartAgentStreamRunAction.php`** — synchronous preflight (`EnforceAiUsageLimit` →
  preserves the existing `402` JSON), creates the run row, keeps the current `dispatchSummarizationIfNeeded`
  + `memoryExtraction->dispatchIfEligible` calls (relocated from `BuildAssistantAgentAction`), dispatches
  the job `afterCommit`, returns `AgentStreamRunData`.
- **`app/Actions/ReplayAgentStreamAction.php`** — generator: (a) replay chunks `sequence > from`
  ordered, mapped to Vercel wire shape by type; (b) poll for new chunks every `~400ms` until a terminal
  chunk OR `run.status in (completed, failed)` OR `expires_at` elapsed; (c) emit deferred finish + `[DONE]`.
  Includes a **stalled-run guard**: if the run stays `queued` past a few seconds (no worker), emit a
  terminal error so the client fails gracefully instead of hanging.
- **`app/Support/Streaming/VercelChunkStreamer.php`** — the single helper that converts stored
  `toArray()` payloads → Vercel wire objects and reproduces `CanStreamUsingVercelProtocol`'s framing
  exactly: one `start`, skip orphan tool results, defer the single `finish`, trailing `data: [DONE]`.
  Used by **both** the POST tail and the GET resume so framing can never drift between them.
- **Models:** `app/Models/AgentStreamRun.php` (`HasUlids`), `app/Models/AgentStreamChunk.php`.
- **DTOs:** `app/Data/AgentStreamRunData.php`, `app/Data/StreamChunkData.php` (spatie/laravel-data).
- **Migrations:** `create_agent_stream_runs_table`, `create_agent_stream_chunks_table`.
- **Prune command + schedule** (Phase 5): delete runs/chunks past `expires_at`.

Edits:

- **`app/Http/Controllers/ChatController.php`** — `stream()` calls `StartAgentStreamRunAction` then
  returns the `ReplayAgentStreamAction` tail (from 0) as a Symfony `StreamedResponse` with the exact
  `CanStreamUsingVercelProtocol` headers (`Cache-Control: no-cache, no-transform`,
  `Content-Type: text/event-stream`, `x-vercel-ai-ui-message-stream: v1`). Add `resume(Conversation, string $run)`
  GET method delegating to `ReplayAgentStreamAction(from = ?from)`. Add the running-run lookup to
  `create()`'s Inertia render as a lazy `activeStream` prop.
- **`app/Http/Controllers/Api/V2/ChatController.php`** — same two changes. **v2 POST keeps returning the
  SSE body inline** (the tail), *not* JSON — so the external Health Sync mobile client's POST contract
  stays byte-compatible. Add the GET resume method.
- **`routes/web.php`** — `GET chat/stream/{conversation}/runs/{run}/resume` →
  `Web\ChatController@resume`, name `chat.stream.resume`, inside the existing auth group with the
  `DisableResponseBuffering` middleware.
- **`routes/api.php`** — `GET v2/chat/conversations/{conversation}/runs/{run}/resume`, name
  `api.v2.chat.stream.resume`, under the same `auth:sanctum` + `abilities:chat:converse` group.

## Frontend components

- **`resources/js/hooks/use-chat-stream.ts`** — keep `DefaultChatTransport` + the existing `402`
  fetch interceptor untouched. Pass `resume: true` to `useChat`. Add `prepareReconnectToStreamRequest`
  that returns the resume GET URL (built with the Wayfinder `chat.stream.resume` helper) for the active
  `runId` with the last-seen `sequence` as `?from=`. Learn `activeRunId`/`resumeUrl` from a custom
  `data-stream-run` data part the POST emits at stream start. Replay is idempotent and the SDK de-dupes
  parts by id, so an exact cursor is an optimization, not a correctness requirement (default `from=0`
  is safe).
- **`resources/js/pages/chat/create-chat.tsx`** — on mount, read the new lazy `activeStream`
  (`{ runId, lastSequence } | null`) prop; if present, `resume: true` reattaches a mid-generation reload
  instead of losing it. Persist `activeRunId` to `sessionStorage` keyed by `conversationId` so a hard
  reload can build the resume request before the first reconnect. Keep the existing
  `onFinish → router.reload({ only: ['creditWarning'] })`.
- **`resources/js/types/chat.ts`** — add `activeStream?: { runId: string; lastSequence: number } | null`.
- **No change to `chat-messages.tsx`** — replayed chunks arrive as ordinary Vercel parts, including
  mid-stream `data-approval` cards (`extractApprovalPayload` reads `tool_results`).

## HITL approval — already turn-bounded, minimal handling

`AgentApprovalResolved` is a plain in-process event (no `ShouldBroadcast`), and `LogHealthEntry` returns
a `pending_approval` tool result while the stream **continues**. Under the durable model:

- The `ToolCall` + `ToolResult(pending_approval)` events persist as ordinary chunks → a reconnecting
  client replays them and re-renders the approval card; a full reload reconstructs it from the finalized
  message's `tool_results` via `BuildConversationMessagesAction`. Both paths intact, no special code.
- **Durability win:** because generation lives in the job, the user can approve/reject (existing
  `ApprovalController → ExecuteApprovalJob`, already queued/DB-backed with retries) and reload **without
  killing generation**; post-approval text lands as new chunks the tail picks up.
- **Critical correctness item:** the job **must** `Context::add('chat.channel', $run->channel)` before
  running the agent — `LogHealthEntry` gates approval on `Context::get('chat.channel')`, and the web
  request's Context does **not** propagate into the queued worker.
- The approval state machine (encrypted payload, `AgentApprovalResolved`, Telegram notify) is unchanged.

## Usage accounting — no double-charge by construction

- The LLM runs **exactly once**, in the job. POST tail and GET resume are **read-only** ledger replays
  that never re-invoke `AgentRunner`.
- `EnforceAiUsageLimit` runs synchronously at request time (preserving `402`) and is **not** re-run on
  resume.
- `TrackAiUsage` fires from `AgentStreamed` once per turn; its `ai_usage_{invocationId}` cache guard
  de-dupes. Two implementation cautions: (1) the guard's 5-min TTL can be shorter than a long
  generation — key dedup on the **persisted `runs.invocation_id`** rather than the cache alone; (2)
  `TrackAiUsage` reads `request->user()` (null in the worker) then falls back to `getUserFromAgent()` —
  works because the agent is invoked `->continue(as: $user)`, so attribution is correct in the job.

## API v2 compatibility

The external Health Sync app's `POST v2/chat/conversations/{conversation}/stream` stays **byte-compatible**:
same request body (`ChatStreamRequest`), same inline Vercel-protocol SSE body to completion. Resume is
**purely additive** (new GET endpoint the app can adopt later). The custom `data-stream-run` part is an
extra SSE part a Vercel-protocol client ignores if unrecognized. **Net: zero breaking change for v2.**

## Reused primitives (no in-place vendor edits)

`StreamableAgentResponse->each()` (per-event tap), `->then()`/`getIterator()` (still finalizes the
message via `RememberConversation`), `StreamEvent::toArray()/type()`, the
`CanStreamUsingVercelProtocol` framing *algorithm* (re-implemented over stored arrays in
`VercelChunkStreamer`), `DatabaseConversationStore` + `agent_conversation_messages` +
`BuildConversationMessagesAction`, `AgentRunner->runWithConversation`, `EnforceAiUsageLimit` +
`TrackAiUsage` + the `invocationId` cache, the full approval stack, `SummarizeConversationJob` + memory
dispatch, `DisableResponseBuffering`, the `402` fetch interceptor, and the installed-but-unused
`useChat` resume / `reconnectToStream` / `prepareReconnectToStreamRequest`. `BroadcastAgent` is the
*template* for the job's shape — replicated, never subclassed/patched.

## Incremental phases (each independently shippable)

1. **Durable ledger behind the current synchronous flow** — add tables, models, `StreamChunkStore` +
   DB impl, DTOs, and tap the *existing* synchronous controller stream with `->each()` to persist chunks.
   Controller still returns `StreamableAgentResponse`. No client/queue change. Proves persistence,
   ordering, `UNIQUE(run_id, sequence)`, partial-answer capture; valuable on its own as crash forensics.
2. **Move generation to the queue** — `StreamAgentRunJob` + `StartAgentStreamRunAction`; controllers
   dispatch and return the POST SSE tail (replay from 0). Frees the worker; handles Context
   re-establishment, `failed()` terminal chunk, in-job usage attribution. Happy-path UX identical; no
   frontend change yet. Requires a running queue worker.
3. **Resume endpoint** — `ReplayAgentStreamAction` GET + `VercelChunkStreamer` + routes (web + v2),
   `204 = null` semantics, replay-then-tail loop. Backend resume usable by any client without touching
   the frontend.
4. **Frontend native resume** — `use-chat-stream.ts` (`resume: true` + `prepareReconnectToStreamRequest`
   + `data-stream-run`) and `create-chat.tsx` (`activeStream` prop). Delivers the user-visible seamless
   resume.
5. **Hardening** — scheduled prune of expired runs/chunks; optional `text_delta` coalescing (~50–100ms)
   to cut write amplification; stalled-run startup guard; and (optional, gated on an infra decision) a
   Reverb tail swap (only the tail loop changes — ledger, resume token, and HITL logic untouched).

## Risks & mitigations

| Risk | Mitigation |
|---|---|
| No broadcast infra (log driver) | Design around it: DB-poll tail; Reverb documented as a later tail-only swap. |
| Queue-worker dependency (POST tail stalls with no worker) | Stalled-run guard in the tail loop; document a supervised prod worker (dev `composer dev` already runs `queue:listen`). |
| Worker loses `chat.channel`/`chat.conversation_id` Context (breaks approval gating) | Re-add Context at the top of `StreamAgentRunJob::handle()` from the run row. **High-priority correctness item.** |
| Partial answer lost on hard job failure (`->then()` never fires) | `failed()` best-effort persists a partial assistant message from `text_delta` chunks. |
| Usage dedup TTL (5 min) < long generation | Key dedup on persisted `runs.invocation_id`, not the cache alone; never retry without reusing the same `invocation_id`. |
| Vercel re-serialization drift vs vendor trait | Single `VercelChunkStreamer` helper; reproduce the framing exactly; re-verify on SDK upgrades. |
| Sequence assignment race | Atomic `runs.next_sequence` under `lockForUpdate` + `UNIQUE(run_id, sequence)`; single producer per run. |
| Write amplification (one row per delta) | Optional `text_delta` coalescing (Phase 5) preserving `message_id`/order; TTL prune. |
| `main` must stay community-safe | All new code is host-app code; no `Acara\AcaraCore` imports, no private composer dep; `StreamChunkStore` contract + `bindIf` default keep it overridable by the private package. |

## Decisions taken (sensible defaults, change on request)

- **Live tail = DB polling now**, Reverb deferred to an optional Phase 5 swap (matches verified infra +
  the "durability, no new infra" framing).
- **Run/chunk TTL = 30 min**, daily prune; resume of older runs falls back to message history.
- **v2 POST stays SSE-inline** (byte-compatible); GET resume is additive/opt-in for mobile.
- **No `text_delta` coalescing in v1** (exact fidelity); revisit as Phase 5 hardening if write volume bites.
- **Poll interval = 400 ms** default, configurable.

Open items genuinely needing your input before/at Phase 2: confirm a **supervised production queue
worker** is available (hard prerequisite for durability), and whether mobile should adopt resume now or
stay POST-only.

## Verification (manual — no automated tests per request)

- **Phase 1:** send a message; confirm `agent_stream_chunks` fills in order with dense `sequence` and a
  terminal `stream_end`; kill the connection mid-stream and confirm chunks + a partial message survive.
- **Phase 2:** confirm `composer dev`'s queue worker runs the job; the POST still streams to completion;
  exactly one `ai_usages` row per turn; an over-limit user still gets `402`.
- **Phase 3:** `curl` the GET resume with `?from=0` mid-run → replays then tails to `[DONE]`; with
  `?from=<last>` after completion → `204`.
- **Phase 4:** in the browser, start a long generation, reload mid-stream → it reattaches and finishes;
  open a second tab on the same conversation → both tail correctly; trigger a health-log approval card,
  reload, confirm the card re-renders and approval still executes.
- The one check worth automating despite the no-tests scope: a byte-compatibility assertion that
  `VercelChunkStreamer` output for a recorded chunk sequence matches the vendor synchronous Vercel form
  (prevents `@ai-sdk/react` desync). Optional, flagged for when you want it.
