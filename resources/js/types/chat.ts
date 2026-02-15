import { ChatMode } from '@/pages/chat/chat-input';
import { UIMessage } from '@ai-sdk/react';

export type { UIMessage } from '@ai-sdk/react';

export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

export interface ChatPageProps {
    conversationId?: string;
    messages: UIMessage[];
    mode: ChatMode;
    [key: string]: unknown;
}
