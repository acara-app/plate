import type { Context } from 'hono'
import type { HonoEnv } from '../env'

export type InternalMessagePart =
  | { type: 'text'; text: string }
  | { type: 'image'; url: string; mediaType?: string }
  | { type: 'file'; url: string; mediaType?: string; name?: string }

export interface InternalMessage {
  id: string
  role: 'user'
  platform: string
  platformUserId: string
  platformMessageId?: string
  platformChatId?: string
  parts: InternalMessagePart[]
  receivedAt: number
}

export interface OutgoingReply {
  platform: string
  platformUserId: string
  platformChatId?: string
  inReplyToMessageId?: string
  text: string
}

export type ReceiveResult =
  | { kind: 'ok'; message: InternalMessage }
  | { kind: 'invalid_signature' }
  | { kind: 'ignored'; reason?: string }

export interface PlatformAdapter {
  readonly name: string

  receive(c: Context<HonoEnv>): Promise<ReceiveResult>

  send(c: Context<HonoEnv>, reply: OutgoingReply): Promise<void>

  describeLinking(code: string): string
}
