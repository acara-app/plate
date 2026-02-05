import { cn } from '@/lib/utils';
import type { ChatStatus } from '@/types/chat';
import { type UIMessage } from '@ai-sdk/react';
import { Bot, User } from 'lucide-react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

interface ChatMessagesProps {
    messages: UIMessage[];
    status: ChatStatus;
}

function EmptyState() {
    return (
        <div className="flex flex-1 flex-col items-center justify-center text-center">
            <div className="mb-4 rounded-full bg-linear-to-br from-emerald-500 to-teal-600 p-4">
                <Bot className="size-8 text-white" />
            </div>
            <h2 className="mb-2 text-xl font-semibold text-foreground">
                How can I help you today?
            </h2>
            <p className="max-w-md text-sm text-muted-foreground">
                Ask me anything about nutrition, meal planning, or healthy
                eating habits.
            </p>
        </div>
    );
}

function MessageAvatar({ role }: { role: string }) {
    const isUser = role === 'user';

    return (
        <div
            className={cn(
                'flex size-8 shrink-0 items-center justify-center rounded-full',
                isUser
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-linear-to-br from-emerald-500 to-teal-600 text-white',
            )}
        >
            {isUser ? <User className="size-4" /> : <Bot className="size-4" />}
        </div>
    );
}

function MessagePart({ part }: { part: UIMessage['parts'][number] }) {
    switch (part.type) {
        case 'text':
            return (
                <div className="prose prose-sm max-w-none dark:prose-invert">
                    <Markdown remarkPlugins={[remarkGfm]}>{part.text}</Markdown>
                </div>
            );
        case 'reasoning':
            return (
                <div className="prose prose-sm max-w-none text-muted-foreground italic dark:prose-invert">
                    <Markdown remarkPlugins={[remarkGfm]}>{part.text}</Markdown>
                </div>
            );
        case 'source-url':
            return (
                <a
                    href={part.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary underline"
                >
                    {part.title ?? part.url}
                </a>
            );
        case 'file':
            return (
                <div className="text-muted-foreground">📎 {part.filename}</div>
            );
        case 'step-start':
        case 'source-document':
        case 'dynamic-tool':
            return null;
        default:
            return null;
    }
}

function UserBubble({ message }: { message: UIMessage }) {
    const textContent = message.parts
        ?.filter((part) => part.type === 'text')
        .map((part) => (part.type === 'text' ? part.text : ''))
        .join('');

    return (
        <div className="flex justify-end gap-3">
            <div className="max-w-[80%] rounded-2xl rounded-br-md bg-primary px-4 py-3 text-primary-foreground shadow-sm">
                <p className="text-sm">{textContent}</p>
            </div>
            <MessageAvatar role="user" />
        </div>
    );
}

function AssistantBubble({ message }: { message: UIMessage }) {
    return (
        <div className="flex gap-3">
            <MessageAvatar role="assistant" />
            <div className="max-w-[80%] rounded-2xl rounded-bl-md bg-muted px-4 py-3 text-foreground shadow-sm">
                <div className="space-y-2 text-sm">
                    {message.parts?.map((part, index) => (
                        <MessagePart key={index} part={part} />
                    ))}
                </div>
            </div>
        </div>
    );
}

function MessageBubble({ message }: { message: UIMessage }) {
    return message.role === 'user' ? (
        <UserBubble message={message} />
    ) : (
        <AssistantBubble message={message} />
    );
}

function StreamingIndicator() {
    return (
        <div className="flex gap-3">
            <MessageAvatar role="assistant" />
            <div className="flex items-center gap-1 rounded-2xl bg-muted px-4 py-3">
                <span className="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.3s]" />
                <span className="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.15s]" />
                <span className="size-2 animate-bounce rounded-full bg-muted-foreground" />
            </div>
        </div>
    );
}

export default function ChatMessages({ messages, status }: ChatMessagesProps) {
    if (messages.length === 0) {
        return <EmptyState />;
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-4 overflow-y-auto">
            {messages.map((message) => (
                <MessageBubble key={message.id} message={message} />
            ))}
            {status === 'streaming' && <StreamingIndicator />}
        </div>
    );
}
