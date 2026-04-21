import type { Bindings } from '../env'
import { signHmac } from '../lib/hmac'
import type { Logger } from '../lib/logger'
import type { ChatTurnRequest, ChatTurnResult } from './types'

export interface PlateClient {
  createChatTurn(
    platform: string,
    platformUserId: string,
    body: ChatTurnRequest,
  ): Promise<ChatTurnResult>
}

interface ChatTurnCreatedPayload {
  plate_user_id: string
  conversation_id: string
  response: string
}

interface ChatTurnConflictPayload {
  linking_code: string
  expires_at: string
}

function chatTurnsPath(platform: string, platformUserId: string): string {
  return (
    '/api/v2/messaging/platforms/' +
    encodeURIComponent(platform) +
    '/users/' +
    encodeURIComponent(platformUserId) +
    '/chat-turns'
  )
}

export function createPlateClient(env: Bindings, logger: Logger): PlateClient {
  async function post<TRes>(
    path: string,
    body: unknown,
  ): Promise<{ status: number; parsed: TRes | null }> {
    const url = new URL(path, env.PLATE_APP_URL).toString()
    const rawBody = JSON.stringify(body)
    const timestamp = Math.floor(Date.now() / 1000).toString()
    const signature = await signHmac(env.SIDECAR_HMAC_SECRET, `${timestamp}.${rawBody}`)

    logger.debug('plate.request', { method: 'POST', path, timestamp })

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        accept: 'application/json',
        'content-type': 'application/json',
        'x-sidecar-timestamp': timestamp,
        'x-sidecar-signature': signature,
      },
      body: rawBody,
    })

    const contentType = response.headers.get('content-type') ?? ''
    let parsed: TRes | null = null
    if (contentType.includes('application/json')) {
      parsed = (await response.json().catch(() => null)) as TRes | null
    }
    return { status: response.status, parsed }
  }

  return {
    async createChatTurn(platform, platformUserId, body) {
      const path = chatTurnsPath(platform, platformUserId)
      const { status, parsed } = await post<
        ChatTurnCreatedPayload | ChatTurnConflictPayload
      >(path, body)

      if (status === 201 && parsed && 'conversation_id' in parsed) {
        return {
          status: 'created',
          plateUserId: parsed.plate_user_id,
          conversationId: parsed.conversation_id,
          response: parsed.response,
        }
      }
      if (status === 409 && parsed && 'linking_code' in parsed) {
        return {
          status: 'link_required',
          linkingCode: parsed.linking_code,
          expiresAt: parsed.expires_at,
        }
      }

      logger.error('plate.response.unexpected', { path, status })
      throw new Error(`Plate ${path} responded ${status}`)
    },
  }
}
