import { Hono } from 'hono'
import { assertEnv, type HonoEnv } from './env'
import { MOCK_REPLY_KEY_PREFIX } from './platforms/mock/adapter'
import { listAdapters } from './platforms/registry'
import { health } from './routes/health'
import { webhooks } from './routes/webhooks'

export const app = new Hono<HonoEnv>()

app.use('*', async (c, next) => {
  c.set('requestId', c.req.header('cf-ray') ?? crypto.randomUUID())
  await next()
})

app.get('/', (c) =>
  c.json({
    service: 'acara-chat-sdk',
    status: 'ok',
    adapters: listAdapters(),
  }),
)

app.route('/', health)
app.route('/', webhooks)

// Dev-only: inspect the last reply a mock-adapter user received.
// Gated behind LOG_LEVEL=debug so production (info+) does not expose it.
app.get('/debug/last-reply/mock/:user', async (c) => {
  if (c.env.LOG_LEVEL !== 'debug') return c.json({ error: 'not_found' }, 404)
  const raw = await c.env.SIDECAR_USER_CACHE.get(`${MOCK_REPLY_KEY_PREFIX}${c.req.param('user')}`)
  if (!raw) return c.json({ status: 'not_found' }, 404)
  return c.json(JSON.parse(raw))
})

app.onError((err, c) => {
  console.error(
    JSON.stringify({
      level: 'error',
      msg: 'unhandled_error',
      error: err.message,
      stack: err.stack,
      requestId: c.get('requestId'),
    }),
  )
  return c.json({ error: 'internal_error' }, 500)
})

app.notFound((c) => c.json({ error: 'not_found' }, 404))

export default {
  async fetch(request: Request, env: unknown, ctx: ExecutionContext): Promise<Response> {
    assertEnv(env as Record<string, unknown>)
    return app.fetch(request, env as HonoEnv['Bindings'], ctx)
  },
} satisfies ExportedHandler<HonoEnv['Bindings']>
