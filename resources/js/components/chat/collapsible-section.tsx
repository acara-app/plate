import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { type ReactNode, useState } from 'react';

const TONE_CLASSES = {
    muted: 'text-muted-foreground',
    active: 'text-emerald-600 dark:text-emerald-400',
    error: 'text-red-500 dark:text-red-400',
} as const;

export function CollapsibleSection({
    icon,
    label,
    labelClassName,
    tone = 'muted',
    trailing,
    preview,
    announce,
    open: controlledOpen,
    defaultOpen = false,
    onOpenChange,
    children,
}: {
    icon: ReactNode;
    label: string;
    labelClassName?: string;
    tone?: keyof typeof TONE_CLASSES;
    trailing?: ReactNode;
    preview?: ReactNode;
    announce?: string;
    open?: boolean;
    defaultOpen?: boolean;
    onOpenChange?: (open: boolean) => void;
    children: ReactNode;
}) {
    const [internalOpen, setInternalOpen] = useState(defaultOpen);
    const open = controlledOpen ?? internalOpen;

    const handleOpenChange = (next: boolean) => {
        if (controlledOpen === undefined) {
            setInternalOpen(next);
        }

        onOpenChange?.(next);
    };

    return (
        <Collapsible
            open={open}
            onOpenChange={handleOpenChange}
            className="rounded-lg border border-border/40 bg-muted/30 backdrop-blur-sm transition-colors duration-200"
        >
            <CollapsibleTrigger
                aria-label={label}
                className={cn(
                    'group flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium transition-all duration-200 hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                    TONE_CLASSES[tone],
                )}
            >
                <span className="shrink-0 transition-transform duration-200 group-data-[state=open]:rotate-180 motion-reduce:transition-none">
                    <ChevronDown className="size-3.5" aria-hidden="true" />
                </span>
                {icon}
                <span className={cn('min-w-0 flex-1 truncate', labelClassName)}>
                    {label}
                </span>
                {trailing}
            </CollapsibleTrigger>

            {!open && preview}

            <CollapsibleContent className="overflow-hidden data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down motion-reduce:animate-none">
                {children}
            </CollapsibleContent>

            {announce && (
                <span role="status" className="sr-only">
                    {announce}
                </span>
            )}
        </Collapsible>
    );
}
