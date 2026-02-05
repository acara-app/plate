// Re-export from Vercel AI SDK v6
export type { UIMessage } from '@ai-sdk/react';

export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

export interface ChatPageProps {
    conversationId?: string;
    [key: string]: unknown;
}
