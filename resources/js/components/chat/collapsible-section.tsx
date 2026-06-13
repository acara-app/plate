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
    active: 'text-emerald-700 dark:text-emerald-300',
    error: 'text-red-600 dark:text-red-400',
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
            className="rounded-lg border border-border/60 bg-muted/40 dark:bg-muted/20"
        >
            <CollapsibleTrigger
                aria-label={label}
                className={cn(
                    'group flex min-h-11 w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium transition-colors outline-none hover:bg-foreground/[0.03] focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    TONE_CLASSES[tone],
                )}
            >
                {icon}
                <span className={cn('min-w-0 flex-1 truncate', labelClassName)}>
                    {label}
                </span>
                {trailing}
                <ChevronDown
                    className="size-3.5 shrink-0 transition-transform duration-200 group-data-[state=open]:rotate-180 motion-reduce:transition-none"
                    aria-hidden="true"
                />
            </CollapsibleTrigger>

            {!open && preview}

            <CollapsibleContent>{children}</CollapsibleContent>

            {announce && (
                <span role="status" className="sr-only">
                    {announce}
                </span>
            )}
        </Collapsible>
    );
}
