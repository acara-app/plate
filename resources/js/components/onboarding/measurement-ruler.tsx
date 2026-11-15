import { AUTH_PRESSABLE } from '@/components/auth/auth-screen';
import { cn } from '@/lib/utils';
import { Minus, Plus } from 'lucide-react';
import {
    memo,
    type PointerEvent,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';

const TICK = 10;

const STEPPER = cn(
    'absolute bottom-0 hidden size-9 items-center justify-center rounded-full bg-background text-foreground shadow-[0_1px_2px_rgb(0_0_0/0.08),0_4px_10px_-4px_rgb(0_0_0/0.2)] transition-[scale] duration-150 ease-out active:scale-90 dark:bg-neutral-700 [@media(hover:hover)_and_(pointer:fine)]:flex',
    AUTH_PRESSABLE,
);

const Ticks = memo(function Ticks({
    count,
    major,
    min,
    step,
}: {
    count: number;
    major: number;
    min: number;
    step: number;
}) {
    return Array.from({ length: count + 1 }, (_, tick) => {
        const value = Number((min + tick * step).toFixed(2));
        const isMajor = value % major === 0;

        return (
            <span
                key={tick}
                className="relative flex h-full w-2.5 shrink-0 snap-center items-end justify-center"
            >
                <span
                    className={cn(
                        'w-[1.5px] rounded-full',
                        isMajor
                            ? 'h-6 bg-foreground/45'
                            : 'h-3.5 bg-foreground/20',
                    )}
                />
                {isMajor && (
                    <span className="absolute top-0 text-[0.6875rem] font-medium text-muted-foreground tabular-nums">
                        {major === 12 ? `${value / 12}′` : value}
                    </span>
                )}
            </span>
        );
    });
});

export function MeasurementRuler({
    label,
    decreaseLabel,
    increaseLabel,
    min,
    max,
    step,
    start,
    value,
    onChange,
}: {
    label: string;
    decreaseLabel: string;
    increaseLabel: string;
    min: number;
    max: number;
    step: number;
    start: number;
    value: number | null;
    onChange: (value: number) => void;
}) {
    const track = useRef<HTMLDivElement>(null);
    const touched = useRef(false);
    const drag = useRef<{ x: number; left: number } | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const count = Math.round((max - min) / step);
    const major = step < 1 ? 5 : max - min > 60 ? 10 : 12;
    const shown = Math.min(Math.max(value ?? start, min), max);
    const index = Math.round((shown - min) / step);

    useLayoutEffect(() => {
        const element = track.current;

        if (
            element &&
            !drag.current &&
            Math.round(element.scrollLeft / TICK) !== index
        ) {
            element.scrollLeft = index * TICK;
        }
    }, [index]);

    const commit = (next: number) => {
        const clamped = Math.min(Math.max(next, 0), count);

        onChange(Number((min + clamped * step).toFixed(2)));
    };

    const stepBy = (by: number) => {
        touched.current = true;
        commit(index + by);
    };

    const endDrag = (event: PointerEvent<HTMLDivElement>) => {
        if (!drag.current) {
            return;
        }

        const element = event.currentTarget;

        drag.current = null;
        setIsDragging(false);
        element.scrollTo({
            left: Math.round(element.scrollLeft / TICK) * TICK,
            behavior: 'smooth',
        });
    };

    return (
        <div className="relative">
            <div
                ref={track}
                role="slider"
                tabIndex={0}
                aria-label={label}
                aria-valuemin={min}
                aria-valuemax={max}
                aria-valuenow={value ?? undefined}
                onPointerDown={(event) => {
                    touched.current = true;

                    if (event.pointerType !== 'mouse' || event.button !== 0) {
                        return;
                    }

                    drag.current = {
                        x: event.clientX,
                        left: event.currentTarget.scrollLeft,
                    };
                    event.currentTarget.setPointerCapture(event.pointerId);
                    setIsDragging(true);
                }}
                onPointerMove={(event) => {
                    if (drag.current) {
                        event.currentTarget.scrollLeft =
                            drag.current.left -
                            (event.clientX - drag.current.x);
                    }
                }}
                onPointerUp={endDrag}
                onPointerCancel={endDrag}
                onWheel={() => {
                    touched.current = true;
                }}
                onScroll={(event) => {
                    if (!touched.current) {
                        return;
                    }

                    const next = Math.round(
                        event.currentTarget.scrollLeft / TICK,
                    );

                    if (next !== index || value === null) {
                        commit(next);
                    }
                }}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        stepBy(1);
                    }

                    if (
                        event.key === 'ArrowLeft' ||
                        event.key === 'ArrowDown'
                    ) {
                        event.preventDefault();
                        stepBy(-1);
                    }
                }}
                className={cn(
                    'flex h-14 [scrollbar-width:none] items-end overflow-x-auto overscroll-x-contain [mask-image:linear-gradient(to_right,transparent,black_22%,black_78%,transparent)] px-[calc(50%-5px)] outline-none select-none focus-visible:[&+span]:bg-emerald-400 [&::-webkit-scrollbar]:hidden',
                    isDragging
                        ? 'cursor-grabbing snap-none'
                        : 'cursor-grab snap-x snap-mandatory',
                )}
            >
                <Ticks count={count} major={major} min={min} step={step} />
            </div>
            <span
                aria-hidden="true"
                className={cn(
                    'pointer-events-none absolute bottom-0 left-1/2 h-9 w-[3px] -translate-x-1/2 rounded-full transition-colors duration-150 ease-out',
                    value === null ? 'bg-foreground/30' : 'bg-emerald-500',
                )}
            />
            <button
                type="button"
                aria-label={decreaseLabel}
                onClick={() => stepBy(-1)}
                className={cn(STEPPER, 'left-0')}
            >
                <Minus className="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                aria-label={increaseLabel}
                onClick={() => stepBy(1)}
                className={cn(STEPPER, 'right-0')}
            >
                <Plus className="size-4" aria-hidden="true" />
            </button>
        </div>
    );
}
