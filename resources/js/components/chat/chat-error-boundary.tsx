import { CircleAlert } from 'lucide-react';
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface ChatErrorBoundaryProps {
    children: ReactNode;
}

interface ChatErrorBoundaryState {
    hasError: boolean;
}

function RenderFailedNotice() {
    const { t } = useTranslation('common');

    return (
        <p
            role="alert"
            className="flex items-center gap-2 text-sm text-muted-foreground"
        >
            <CircleAlert
                className="size-4 shrink-0 text-red-600 dark:text-red-400"
                strokeWidth={1.75}
                aria-hidden="true"
            />
            {t('chat.error.render')}
        </p>
    );
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
            return <RenderFailedNotice />;
        }

        return this.props.children;
    }
}
