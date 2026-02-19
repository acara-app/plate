import { cn } from '@/lib/utils';
import type { ChatStatus } from '@/types/chat';
import { type UIMessage } from '@ai-sdk/react';
import { AlertCircle, User } from 'lucide-react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

interface ChatMessagesProps {
    messages: UIMessage[];
    status: ChatStatus;
    isSubmitting?: boolean;
}

export function ChatErrorBanner({ error }: { error?: Error }) {
    if (!error) {
        return null;
    }
    return (
        <div className="flex w-full items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/50">
            <AlertCircle className="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
            <div className="flex-1 space-y-1">
                <p className="text-sm font-medium text-red-800 dark:text-red-200">
                    Something went wrong
                </p>
                <p className="text-sm text-red-700 dark:text-red-300">
                    {error.message}
                </p>
            </div>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-1 flex-col items-center justify-center text-center">
            <div className="mb-4 overflow-hidden rounded-full ring-4 ring-emerald-100">
                <img
                    src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp"
                    alt="Altani"
                    className="size-20 object-cover"
                />
            </div>
            <h2 className="mb-2 text-xl font-semibold text-foreground">
                How are you feeling today?
            </h2>
            <p className="max-w-md text-sm text-muted-foreground">
                Your personal AI health coach is here to help with nutrition,
                meal planning, glucose predictions, or just to chat.
            </p>
        </div>
    );
}

function MessageAvatar({ role }: { role: string }) {
    const isUser = role === 'user';

    return (
        <div
            className={cn(
                'flex shrink-0 items-center justify-center overflow-hidden rounded-full',
                isUser ? 'size-8' : 'size-10',
                isUser
                    ? 'bg-primary text-primary-foreground'
                    : 'ring-2 ring-emerald-100',
            )}
        >
            {isUser ? (
                <User className="size-4" />
            ) : (
                <img
                    src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                    alt="Altani"
                    className="h-full w-full object-cover"
                />
            )}
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
    const renderableParts = message.parts?.filter((part) => {
        if (part.type === 'text' || part.type === 'reasoning') {
            return part.text && part.text.trim().length > 0;
        }
        if (part.type === 'source-url' || part.type === 'file') {
            return true;
        }
        return false;
    });

    if (!renderableParts || renderableParts.length === 0) {
        return null;
    }

    return (
        <div className="flex gap-3">
            <MessageAvatar role="assistant" />
            <div className="max-w-[80%] rounded-2xl rounded-bl-md bg-muted px-4 py-3 text-foreground shadow-sm">
                <div className="space-y-2 text-sm">
                    {renderableParts.map((part, index) => (
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

function ThinkingIndicator() {
    return (
        <div className="flex gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <MessageAvatar role="assistant" />
            <div className="flex items-center gap-2 rounded-2xl bg-muted px-4 py-3">
                <div className="flex items-center gap-1">
                    <span className="size-2 animate-pulse rounded-full bg-emerald-500" />
                    <span className="size-2 animate-pulse rounded-full bg-emerald-500 [animation-delay:150ms]" />
                    <span className="size-2 animate-pulse rounded-full bg-emerald-500 [animation-delay:300ms]" />
                </div>
                <span className="text-sm text-muted-foreground">
                    Altani is thinking...
                </span>
            </div>
        </div>
    );
}

function StreamingIndicator() {
    return (
        <div className="flex gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <MessageAvatar role="assistant" />
            <div className="flex items-center gap-2 rounded-2xl bg-muted px-4 py-3">
                <div className="flex items-center gap-1">
                    <span className="size-2 animate-bounce rounded-full bg-emerald-500 [animation-delay:-0.3s]" />
                    <span className="size-2 animate-bounce rounded-full bg-emerald-500 [animation-delay:-0.15s]" />
                    <span className="size-2 animate-bounce rounded-full bg-emerald-500" />
                </div>
                <span className="text-sm text-muted-foreground">
                    Altani is typing...
                </span>
            </div>
        </div>
    );
}

export default function ChatMessages({
    messages,
    status,
    isSubmitting,
}: ChatMessagesProps) {
    if (messages.length === 0) {
        return <EmptyState />;
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-4">
            {messages.map((message) => (
                <MessageBubble key={message.id} message={message} />
            ))}
            {isSubmitting && <ThinkingIndicator />}
            {status === 'streaming' && <StreamingIndicator />}
        </div>
    );
}
