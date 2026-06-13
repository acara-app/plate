import type { ProviderToolData } from '@/types/chat';
import {
    Check,
    FileSearch,
    FileText,
    Globe,
    type LucideIcon,
    Search,
    Zap,
} from 'lucide-react';
import { RunningDots } from './running-dots';

function describe(tool: ProviderToolData): { label: string; Icon: LucideIcon } {
    const done = tool.status === 'complete';

    if (tool.kind === 'web_search') {
        return done
            ? { label: 'Searched the web', Icon: Globe }
            : { label: 'Searching the web…', Icon: Search };
    }

    if (tool.kind === 'web_fetch') {
        return done
            ? { label: 'Read sources', Icon: FileText }
            : { label: 'Reading sources…', Icon: FileSearch };
    }

    return { label: done ? 'Done' : 'Working…', Icon: Zap };
}

export function ProviderToolRow({ tool }: { tool: ProviderToolData }) {
    const { label, Icon } = describe(tool);

    return (
        <div
            aria-label={label}
            className="flex items-center gap-2 px-1 py-0.5 text-xs text-muted-foreground"
        >
            <Icon className="size-3.5 shrink-0" aria-hidden="true" />
            <span className="min-w-0 flex-1 truncate">{label}</span>
            {tool.status === 'complete' ? (
                <Check
                    className="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
            ) : (
                <RunningDots />
            )}
        </div>
    );
}
