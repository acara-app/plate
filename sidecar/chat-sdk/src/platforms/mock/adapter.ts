import type { Context } from 'hono'
import { z } from 'zod'
import type { HonoEnv } from '../../env'
import type { OutgoingReply, PlatformAdapter, ReceiveResult } from '../types'

const MockBodySchema = z.object({
  from: z.string().min(1),
  text: z.string().min(1),
  chatId: z.string().optional(),
  messageId: z.string().optional(),
})

export const MOCK_REPLY_KEY_PREFIX = 'mock:last-reply:'

export const mockAdapter: PlatformAdapter = {
  name: 'mock',

  async receive(c: Context<HonoEnv>): Promise<ReceiveResult> {
    const raw = await c.req.json().catch(() => null)
    const parsed = MockBodySchema.safeParse(raw)
    if (!parsed.success) {
      return { kind: 'ignored', reason: 'invalid_body' }
    }
    const { from, text, chatId, messageId } = parsed.data
    return {
      kind: 'ok',
      message: {
        id: messageId ?? crypto.randomUUID(),
        role: 'user',
        platform: 'mock',
        platformUserId: from,
        platformMessageId: messageId,
        platformChatId: chatId,
        parts: [{ type: 'text', text }],
        receivedAt: Date.now(),
      },
    }
  },

  async send(c: Context<HonoEnv>, reply: OutgoingReply): Promise<void> {
    const key = `${MOCK_REPLY_KEY_PREFIX}${reply.platformUserId}`
    await c.env.SIDECAR_USER_CACHE.put(
      key,
      JSON.stringify({ ...reply, sentAt: Date.now() }),
      { expirationTtl: 300 },
    )
  },

  describeLinking(code: string): string {
    return [
      'Link your Plate account to start chatting here.',
      `Open Plate → Settings → Integrations and paste this code: ${code}`,
    ].join('\n')
  },
}
