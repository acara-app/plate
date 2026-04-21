import { Hono } from 'hono'
import type { HonoEnv } from '../env'
import { createLogger } from '../lib/logger'
import type { InternalMessage } from '../platforms/types'
import { getAdapter } from '../platforms/registry'
import { createPlateClient } from '../plate/client'
import { setCachedLink } from '../services/user-cache'

export const webhooks = new Hono<HonoEnv>()

webhooks.post('/webhooks/:platform', async (c) => {
  const platform = c.req.param('platform')
  const adapter = getAdapter(platform)
  if (!adapter) {
    return c.json({ error: 'unknown_platform', platform }, 404)
  }

  const logger = createLogger(c.env.LOG_LEVEL, {
    requestId: c.get('requestId'),
    platform,
  })

  const received = await adapter.receive(c)
  if (received.kind === 'invalid_signature') {
    logger.warn('webhook.verify.failed')
    return c.json({ error: 'invalid_signature' }, 401)
  }
  if (received.kind === 'ignored') {
    logger.info('webhook.parse.ignored', { reason: received.reason })
    return c.json({ status: 'ignored', reason: received.reason ?? null })
  }

  const { message } = received
  const plate = createPlateClient(c.env, logger)

  try {
    const turn = await plate.createChatTurn(message.platform, message.platformUserId, {
      message: textOf(message),
      platform_message_id: message.platformMessageId,
    })

    if (turn.status === 'link_required') {
      logger.info('webhook.link_required')
      await adapter.send(c, {
        platform: message.platform,
        platformUserId: message.platformUserId,
        platformChatId: message.platformChatId,
        text: adapter.describeLinking(turn.linkingCode),
      })
      return c.json({ status: 'link_required' })
    }

    await setCachedLink(c.env, message.platform, message.platformUserId, {
      plateUserId: turn.plateUserId,
      linkedAt: Date.now(),
    })

    await adapter.send(c, {
      platform: message.platform,
      platformUserId: message.platformUserId,
      platformChatId: message.platformChatId,
      inReplyToMessageId: message.platformMessageId,
      text: turn.response,
    })
    return c.json({ status: 'ok', conversation_id: turn.conversationId })
  } catch (error) {
    logger.error('webhook.dispatch.error', {
      error: error instanceof Error ? error.message : String(error),
    })
    return c.json({ error: 'internal_error' }, 500)
  }
})

function textOf(message: InternalMessage): string {
  return message.parts
    .filter((part): part is { type: 'text'; text: string } => part.type === 'text')
    .map((part) => part.text)
    .join('\n')
    .trim()
}
