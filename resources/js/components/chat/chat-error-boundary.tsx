import { AlertCircle } from 'lucide-react';
import { Component, type ErrorInfo, type ReactNode } from 'react';

interface ChatErrorBoundaryProps {
    children: ReactNode;
}

interface ChatErrorBoundaryState {
    hasError: boolean;
}

export class ChatErrorBoundary extends Component<
    ChatErrorBoundaryProps,
    ChatErrorBoundaryState
> {
    state: ChatErrorBoundaryState = { hasError: false };

    static getDerivedStateFromError(): ChatErrorBoundaryState {
        return { hasError: true };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error('Chat message failed to render', error, info);
    }

    render(): ReactNode {
        if (this.state.hasError) {
            return (
                <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-300">
                    <AlertCircle className="size-4 shrink-0" />
                    Something went wrong displaying this message.
                </div>
            );
        }

        return this.props.children;
    }
}
