import { env } from 'cloudflare:test'
import { describe, expect, it } from 'vitest'
import { Hono } from 'hono'
import type { HonoEnv } from '../src/env'
import { mockAdapter } from '../src/platforms/mock/adapter'

describe('mockAdapter', () => {
  describe('receive', () => {
    it('parses a valid body into an InternalMessage', async () => {
      const app = buildReceiveApp()
      const res = await app.request(
        '/test',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: 'alice', text: 'hello' }),
        },
        env,
      )
      const data = (await res.json()) as { kind: string; message?: { platformUserId: string; parts: unknown[] } }

      expect(data.kind).toBe('ok')
      expect(data.message?.platformUserId).toBe('alice')
      expect(data.message?.parts).toEqual([{ type: 'text', text: 'hello' }])
    })

    it('ignores a body missing required fields', async () => {
      const app = buildReceiveApp()
      const res = await app.request(
        '/test',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ oops: true }),
        },
        env,
      )
      const data = (await res.json()) as { kind: string; reason?: string }

      expect(data.kind).toBe('ignored')
      expect(data.reason).toBe('invalid_body')
    })

    it('ignores a non-JSON body', async () => {
      const app = buildReceiveApp()
      const res = await app.request(
        '/test',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: 'not-json',
        },
        env,
      )
      const data = (await res.json()) as { kind: string }

      expect(data.kind).toBe('ignored')
    })

    it('generates a UUID id when messageId is omitted', async () => {
      const app = buildReceiveApp()
      const res = await app.request(
        '/test',
        {
          method: 'POST',
          headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ from: 'alice', text: 'hi' }),
        },
        env,
      )
      const data = (await res.json()) as { message?: { id: string } }
      expect(data.message?.id).toMatch(/^[0-9a-f-]{36}$/i)
    })
  })

  describe('describeLinking', () => {
    it('includes the code verbatim', () => {
      const out = mockAdapter.describeLinking('ABC12345')
      expect(out).toContain('ABC12345')
    })
  })
})

function buildReceiveApp() {
  const app = new Hono<HonoEnv>()
  app.post('/test', async (c) => c.json(await mockAdapter.receive(c)))
  return app
}
