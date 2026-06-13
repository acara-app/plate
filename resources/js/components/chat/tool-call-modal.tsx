import {
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ToolCallData } from '@/types/chat';

function stringifyResult(value: unknown): string {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'string') {
        return value;
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
}

export function ToolCallModal({
    tool,
    label,
}: {
    tool: ToolCallData;
    label: string;
}) {
    const args = tool.args ? JSON.stringify(tool.args, null, 2) : null;
    const result = stringifyResult(tool.result);

    return (
        <DialogContent className="max-h-[80vh] gap-4 overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle className="text-base">{label}</DialogTitle>
                <DialogDescription className="font-mono text-xs">
                    {tool.toolName || 'tool'}
                </DialogDescription>
            </DialogHeader>

            {args && (
                <section>
                    <h3 className="mb-1 text-xs font-medium text-muted-foreground">
                        Arguments
                    </h3>
                    <pre className="overflow-x-auto rounded-md bg-muted p-3 text-xs">
                        {args}
                    </pre>
                </section>
            )}

            {tool.status === 'error' ? (
                <section>
                    <h3 className="mb-1 text-xs font-medium text-red-600 dark:text-red-400">
                        Error
                    </h3>
                    <pre className="overflow-x-auto rounded-md bg-red-50 p-3 text-xs text-red-700 dark:bg-red-950/40 dark:text-red-300">
                        {tool.error ?? 'Tool failed.'}
                    </pre>
                </section>
            ) : result !== '' ? (
                <section>
                    <h3 className="mb-1 text-xs font-medium text-muted-foreground">
                        Result
                    </h3>
                    <pre className="overflow-x-auto rounded-md bg-muted p-3 text-xs">
                        {result}
                    </pre>
                </section>
            ) : tool.status === 'running' ? (
                <p className="text-xs text-muted-foreground">Running…</p>
            ) : null}
        </DialogContent>
    );
}
