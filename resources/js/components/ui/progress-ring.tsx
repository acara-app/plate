import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export function ProgressRing({
    value,
    size = 64,
    strokeWidth = 6,
    indicatorClassName,
    className,
    children,
}: {
    value: number;
    size?: number;
    strokeWidth?: number;
    indicatorClassName?: string;
    className?: string;
    children?: ReactNode;
}) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const progress = Math.min(Math.max(value, 0), 1);

    return (
        <span
            aria-hidden="true"
            className={cn(
                'relative inline-flex shrink-0 items-center justify-center',
                className,
            )}
            style={{ width: size, height: size }}
        >
            <svg
                width={size}
                height={size}
                viewBox={`0 0 ${size} ${size}`}
                className="absolute inset-0 -rotate-90"
            >
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    className="stroke-muted"
                />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - progress)}
                    className={cn(
                        'stroke-primary transition-[stroke-dashoffset] duration-500 ease-out',
                        progress === 0 && 'opacity-0',
                        indicatorClassName,
                    )}
                />
            </svg>
            {children}
        </span>
    );
}
