import { env } from 'cloudflare:test'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { app } from '../src/index'

describe('sidecar routes', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('GET /healthz', () => {
    it('returns ok', async () => {
      const res = await app.request('/healthz', {}, env)
      expect(res.status).toBe(200)
      expect(await res.json()).toEqual({ status: 'ok' })
    })
  })

  describe('POST /webhooks/:platform', () => {
    it('returns 404 for an unknown platform', async () => {
      const res = await app.request(
        '/webhooks/nope',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: '{}',
        },
        env,
      )
      expect(res.status).toBe(404)
    })

    it('returns ignored when the body is malformed', async () => {
      const res = await app.request(
        '/webhooks/mock',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ oops: 1 }),
        },
        env,
      )
      expect(res.status).toBe(200)
      expect(await res.json()).toMatchObject({ status: 'ignored' })
    })

    it('prompts linking when Plate returns 409 Conflict', async () => {
      const plate = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
        new Response(
          JSON.stringify({
            linking_code: 'ABC12345',
            expires_at: new Date(Date.now() + 3600_000).toISOString(),
          }),
          { status: 409, headers: { 'content-type': 'application/json' } },
        ),
      )

      const res = await app.request(
        '/webhooks/mock',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: 'unknown-user', text: 'hi' }),
        },
        env,
      )

      expect(res.status).toBe(200)
      expect(await res.json()).toEqual({ status: 'link_required' })
      expect(plate).toHaveBeenCalledTimes(1)

      const [calledUrl] = plate.mock.calls[0]!
      expect(String(calledUrl)).toContain(
        '/api/v2/messaging/platforms/mock/users/unknown-user/chat-turns',
      )

      const stored = await env.SIDECAR_USER_CACHE.get('mock:last-reply:unknown-user')
      expect(stored).toBeTruthy()
      const reply = JSON.parse(stored ?? '{}') as { text: string }
      expect(reply.text).toContain('ABC12345')
    })

    it('forwards the advisor reply and caches the link on 201 Created', async () => {
      const plate = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
        new Response(
          JSON.stringify({
            plate_user_id: '42',
            conversation_id: 'conv-123',
            response: 'Hello from the advisor!',
          }),
          { status: 201, headers: { 'content-type': 'application/json' } },
        ),
      )

      const res = await app.request(
        '/webhooks/mock',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: 'alice', text: 'hello advisor' }),
        },
        env,
      )

      expect(res.status).toBe(200)
      expect(await res.json()).toMatchObject({ status: 'ok', conversation_id: 'conv-123' })

      const [, init] = plate.mock.calls[0]!
      const signedHeaders = new Headers(init?.headers as HeadersInit)
      expect(signedHeaders.get('x-sidecar-signature')).toMatch(/^[0-9a-f]+$/)
      expect(signedHeaders.get('x-sidecar-timestamp')).toMatch(/^\d+$/)
      expect(init?.method).toBe('POST')

      const stored = await env.SIDECAR_USER_CACHE.get('mock:last-reply:alice')
      expect(stored).toBeTruthy()
      const reply = JSON.parse(stored ?? '{}') as { text: string }
      expect(reply.text).toBe('Hello from the advisor!')

      const cached = await env.SIDECAR_USER_CACHE.get('link:mock:alice')
      expect(cached).toBeTruthy()
      const link = JSON.parse(cached ?? '{}') as { plateUserId: string }
      expect(link.plateUserId).toBe('42')
    })

    it('URL-encodes the platform user id', async () => {
      const plate = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
        new Response(
          JSON.stringify({
            plate_user_id: '7',
            conversation_id: 'c',
            response: 'ok',
          }),
          { status: 201, headers: { 'content-type': 'application/json' } },
        ),
      )

      await app.request(
        '/webhooks/mock',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: '+15551234567', text: 'hi' }),
        },
        env,
      )

      const [calledUrl] = plate.mock.calls[0]!
      expect(String(calledUrl)).toContain(
        '/api/v2/messaging/platforms/mock/users/%2B15551234567/chat-turns',
      )
    })

    it('returns 500 when Plate returns an unexpected status', async () => {
      vi.spyOn(globalThis, 'fetch').mockResolvedValue(
        new Response('server error', { status: 500 }),
      )

      const res = await app.request(
        '/webhooks/mock',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: 'bob', text: 'hey' }),
        },
        env,
      )

      expect(res.status).toBe(500)
      expect(await res.json()).toEqual({ error: 'internal_error' })
    })
  })
})
