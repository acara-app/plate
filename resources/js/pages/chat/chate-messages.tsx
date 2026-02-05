import type { ChatStatus } from '@/types/chat';
import { type UIMessage } from '@ai-sdk/react';
import { Bot, User } from 'lucide-react';

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
            className={`flex size-8 shrink-0 items-center justify-center rounded-full ${
                isUser
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-linear-to-br from-emerald-500 to-teal-600 text-white'
            }`}
        >
            {isUser ? <User className="size-4" /> : <Bot className="size-4" />}
        </div>
    );
}

function MessageBubble({ message }: { message: UIMessage }) {
    const isUser = message.role === 'user';

    return (
        <div className={`flex gap-3 ${isUser ? 'flex-row-reverse' : ''}`}>
            <MessageAvatar role={message.role} />
            <div
                className={`max-w-[80%] rounded-2xl px-4 py-3 ${
                    isUser
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-foreground'
                }`}
            >
                <div className="text-sm whitespace-pre-wrap">
                    {message.parts
                        ?.filter((part) => part.type === 'text')
                        .map((part, index) => (
                            <span key={index}>
                                {part.type === 'text' && part.text}
                            </span>
                        ))}
                </div>
            </div>
        </div>
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
