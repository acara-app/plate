import { cn } from '@/lib/utils';
import type { VisualPartData } from '@/types/chat';
import { useTranslation } from 'react-i18next';
import { VISUAL_SURFACE } from './components/layout';
import { canRenderVisual, ChatVisualRenderer } from './render';

export function ChatVisual({
    visual,
    isStreaming,
}: {
    visual: VisualPartData;
    isStreaming: boolean;
}) {
    const { t } = useTranslation('common');

    if (visual.status === 'pending') {
        return isStreaming ? (
            <div
                role="status"
                data-test="chat-visual-placeholder"
                className={cn(
                    'my-2 h-28 motion-safe:animate-pulse',
                    VISUAL_SURFACE,
                )}
            >
                <span className="sr-only">{t('chat.visual.preparing')}</span>
            </div>
        ) : null;
    }

    if (!canRenderVisual(visual.spec)) {
        return visual.fallback_text.trim() === '' ? null : (
            <p
                data-test="chat-visual-fallback"
                className="text-base leading-7 [overflow-wrap:anywhere] whitespace-pre-line text-foreground/90"
            >
                {visual.fallback_text}
            </p>
        );
    }

    const framed = visual.spec.elements[visual.spec.root]?.type === 'Card';

    return (
        <figure
            aria-label={t('chat.visual.label')}
            data-test="chat-visual"
            className={cn(
                '@container my-2 duration-300 ease-out motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-1',
                !framed && VISUAL_SURFACE,
            )}
        >
            <ChatVisualRenderer spec={visual.spec} state={visual.spec.state} />
        </figure>
    );
}
