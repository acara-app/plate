export interface ChatTurnRequest {
  message: string
  platform_message_id?: string
  attachments?: Array<{ url: string; media_type?: string; name?: string }>
}

export type ChatTurnResult =
  | {
      status: 'created'
      plateUserId: string
      conversationId: string
      response: string
    }
  | {
      status: 'link_required'
      linkingCode: string
      expiresAt: string
    }
