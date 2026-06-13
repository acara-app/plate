import { cn } from '@/lib/utils';
import type { ReasoningData } from '@/types/chat';
import { code } from '@streamdown/code';
import { Brain } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Streamdown } from 'streamdown';
import { CollapsibleSection } from './collapsible-section';

function formatDuration(ms: number): string {
    const totalSeconds = Math.max(0, Math.round(ms / 1000));

    if (totalSeconds < 60) {
        return `${totalSeconds}s`;
    }

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return seconds === 0 ? `${minutes}m` : `${minutes}m ${seconds}s`;
}

export function ReasoningBlock({
    data,
    isStreaming,
}: {
    data: ReasoningData;
    isStreaming?: boolean;
}) {
    const live = data.active && isStreaming === true;
    const [open, setOpen] = useState(live);
    const wasLiveRef = useRef(live);
    const userToggledRef = useRef(false);

    useEffect(() => {
        if (userToggledRef.current) {
            return;
        }

        if (wasLiveRef.current && !live) {
            setOpen(false);
        } else if (!wasLiveRef.current && live) {
            setOpen(true);
        }

        wasLiveRef.current = live;
    }, [live]);

    const hasText = data.text.trim().length > 0;

    if (!live && !hasText) {
        return null;
    }

    const label = live
        ? 'Reasoning'
        : data.completedAt
          ? `Thought for ${formatDuration(data.completedAt - data.startedAt)}`
          : 'Thought process';

    return (
        <CollapsibleSection
            icon={
                <Brain
                    className={cn(
                        'size-3.5 shrink-0 text-emerald-500',
                        live && 'motion-safe:animate-pulse',
                    )}
                    aria-hidden="true"
                />
            }
            label={label}
            labelClassName={cn(live && 'motion-safe:animate-pulse')}
            open={open}
            onOpenChange={(next) => {
                userToggledRef.current = true;
                setOpen(next);
            }}
            announce={live ? undefined : label}
            preview={
                live && hasText ? (
                    <div
                        aria-hidden="true"
                        className="max-h-12 overflow-hidden [mask-image:linear-gradient(to_bottom,transparent,black_60%)] px-3 pb-2 text-xs text-muted-foreground/80 italic"
                    >
                        {data.text}
                    </div>
                ) : undefined
            }
        >
            <div className="max-h-72 overflow-y-auto px-3 pb-2">
                <div className="prose prose-sm max-w-none text-muted-foreground italic dark:prose-invert">
                    <Streamdown animated isAnimating={live} plugins={{ code }}>
                        {data.text}
                    </Streamdown>
                </div>
            </div>
        </CollapsibleSection>
    );
}
