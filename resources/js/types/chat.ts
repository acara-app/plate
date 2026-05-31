import { UIMessage } from '@ai-sdk/react';

export type { UIMessage } from '@ai-sdk/react';

export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

export interface ChatPageProps {
    conversationId: string;
    initialPrompt?: string | null;
    messages: UIMessage[];
    [key: string]: unknown;
}
