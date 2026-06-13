import { Dialog, DialogTrigger } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { ToolCallData } from '@/types/chat';
import {
    Check,
    FileText,
    Globe,
    type LucideIcon,
    NotebookPen,
    Search,
    Wrench,
    X,
} from 'lucide-react';
import { RunningDots } from './running-dots';
import { ToolCallModal } from './tool-call-modal';

const TOOL_ICONS: Record<string, LucideIcon> = {
    log_health_entry: NotebookPen,
    web_search: Globe,
    web_fetch: FileText,
    search_food_reference: Search,
};

export function toolLabel(tool: ToolCallData): string {
    if (tool.title) {
        return tool.title;
    }

    if (!tool.toolName) {
        return 'Tool';
    }

    return (
        tool.toolName.charAt(0).toUpperCase() +
        tool.toolName.slice(1).replace(/_/g, ' ')
    );
}

const PREVIEW_KEYS = [
    'query',
    'q',
    'description',
    'text',
    'message',
    'name',
    'term',
    'food',
    'url',
];

function previewValue(value: unknown): string | null {
    if (typeof value !== 'string' || value.trim() === '') {
        return null;
    }

    return value.length > 60 ? `${value.slice(0, 60)}…` : value;
}

function argsPreview(args: Record<string, unknown> | null): string | null {
    if (!args) {
        return null;
    }

    for (const key of PREVIEW_KEYS) {
        const preview = previewValue(args[key]);

        if (preview !== null) {
            return preview;
        }
    }

    for (const value of Object.values(args)) {
        const preview = previewValue(value);

        if (preview !== null) {
            return preview;
        }
    }

    return null;
}

export function ToolCallCard({
    tool,
    isStreaming,
}: {
    tool: ToolCallData;
    isStreaming?: boolean;
}) {
    const Icon = TOOL_ICONS[tool.toolName] ?? Wrench;
    const label = toolLabel(tool);
    const preview = argsPreview(tool.args);
    const interrupted = tool.status === 'running' && !isStreaming;

    return (
        <Dialog>
            <DialogTrigger
                aria-haspopup="dialog"
                className="flex min-h-10 w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-xs transition-all duration-200 hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                <div
                    className={cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-md transition-colors duration-200',
                        tool.status === 'complete'
                            ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
                            : tool.status === 'error'
                              ? 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400'
                              : interrupted
                                ? 'bg-muted text-muted-foreground'
                                : 'bg-primary/10 text-primary',
                    )}
                >
                    <Icon className="size-3.5" aria-hidden="true" />
                </div>
                <span className="min-w-0 flex-1">
                    <span className="font-medium text-foreground">{label}</span>
                    {preview && (
                        <span className="ml-1.5 text-muted-foreground">
                            {preview}
                        </span>
                    )}
                </span>
                {tool.status === 'complete' ? (
                    <Check
                        className="size-3.5 shrink-0 text-emerald-500"
                        aria-label="Completed"
                    />
                ) : tool.status === 'error' ? (
                    <X
                        className="size-3.5 shrink-0 text-red-500"
                        aria-label="Failed"
                    />
                ) : interrupted ? (
                    <span
                        className="size-1.5 shrink-0 rounded-full bg-muted-foreground/40"
                        aria-label="Stopped"
                    />
                ) : (
                    <RunningDots />
                )}
            </DialogTrigger>
            <ToolCallModal tool={tool} label={label} />
        </Dialog>
    );
}
