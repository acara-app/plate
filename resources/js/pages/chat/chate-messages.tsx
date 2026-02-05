import { type UIMessage } from '@ai-sdk/react';
import { Bot, User } from 'lucide-react';

type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

interface ChatMessagesProps {
    messages: UIMessage[];
    status: ChatStatus;
}

export default function ChatMessages({ messages, status }: ChatMessagesProps) {
    if (messages.length === 0) {
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

    return (
        <div className="flex w-full flex-1 flex-col gap-4 overflow-y-auto">
            {messages.map((message) => (
                <div
                    key={message.id}
                    className={`flex gap-3 ${
                        message.role === 'user' ? 'flex-row-reverse' : ''
                    }`}
                >
                    <div
                        className={`flex size-8 shrink-0 items-center justify-center rounded-full ${
                            message.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-linear-to-br from-emerald-500 to-teal-600 text-white'
                        }`}
                    >
                        {message.role === 'user' ? (
                            <User className="size-4" />
                        ) : (
                            <Bot className="size-4" />
                        )}
                    </div>
                    <div
                        className={`max-w-[80%] rounded-2xl px-4 py-3 ${
                            message.role === 'user'
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
            ))}
            {status === 'streaming' && (
                <div className="flex gap-3">
                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-emerald-500 to-teal-600 text-white">
                        <Bot className="size-4" />
                    </div>
                    <div className="flex items-center gap-1 rounded-2xl bg-muted px-4 py-3">
                        <span className="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.3s]" />
                        <span className="size-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.15s]" />
                        <span className="size-2 animate-bounce rounded-full bg-muted-foreground" />
                    </div>
                </div>
            )}
        </div>
    );
}
