import { cn } from '@/lib/utils';

interface ProgressProps {
    value?: number;
    className?: string;
}

export function Progress({ value = 0, className }: ProgressProps) {
    return (
        <div
            className={cn(
                'relative h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700',
                className,
            )}
        >
            <div
                className="h-full bg-primary transition-all duration-300 ease-in-out"
                style={{ width: `${Math.min(100, Math.max(0, value))}%` }}
            />
        </div>
    );
}
