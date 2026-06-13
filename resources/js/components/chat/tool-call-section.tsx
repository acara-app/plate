import type { ToolCallData } from '@/types/chat';
import { Check, Wrench, X } from 'lucide-react';
import { CollapsibleSection } from './collapsible-section';
import { RunningDots } from './running-dots';
import { ToolCallCard, toolLabel } from './tool-call-card';

function summarize(tools: ToolCallData[], live: boolean): string {
    const running = tools.filter((tool) => tool.status === 'running');
    const failed = tools.filter((tool) => tool.status === 'error');
    const plural = tools.length === 1 ? 'tool' : 'tools';

    if (live && running.length > 0) {
        if (tools.length === 1) {
            return `Running ${toolLabel(running[0])}…`;
        }

        return `${running.length} running…`;
    }

    if (failed.length > 0) {
        return `${tools.length} ${plural} used · ${failed.length} failed`;
    }

    return `${tools.length} ${plural} used`;
}

export function ToolCallSection({
    tools,
    isStreaming,
}: {
    tools: ToolCallData[];
    isStreaming?: boolean;
}) {
    if (tools.length === 0) {
        return null;
    }

    const failed = tools.some((tool) => tool.status === 'error');
    const live =
        isStreaming === true && tools.some((tool) => tool.status === 'running');
    const summary = summarize(tools, live);

    return (
        <CollapsibleSection
            icon={<Wrench className="size-3.5 shrink-0" aria-hidden="true" />}
            label={summary}
            tone={failed ? 'error' : live ? 'active' : 'muted'}
            trailing={
                live ? (
                    <RunningDots />
                ) : failed ? (
                    <X className="size-3.5 shrink-0" aria-hidden="true" />
                ) : (
                    <Check
                        className="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
                    />
                )
            }
        >
            <div className="ml-3 space-y-0.5 border-l-2 border-border/60 py-1 pl-2">
                {tools.map((tool) => (
                    <ToolCallCard
                        key={tool.toolId}
                        tool={tool}
                        isStreaming={isStreaming}
                    />
                ))}
            </div>
        </CollapsibleSection>
    );
}
