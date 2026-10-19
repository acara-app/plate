import { addDaysToKey, startOfWeekKey } from '@/lib/format-local-datetime';
import {
    type DragEvent,
    type KeyboardEvent,
    type MouseEvent,
    type PointerEvent,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';

export default function useWeekScroll(date: string, today: string) {
    const currentWeek = startOfWeekKey(today);
    const selectedWeek = startOfWeekKey(date);
    const [visibleWeek, setVisibleWeek] = useState(
        selectedWeek > currentWeek ? currentWeek : selectedWeek,
    );
    const stripRef = useRef<HTMLDivElement>(null);
    const settleTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
        undefined,
    );
    const suppressClick = useRef(false);
    const drag = useRef<{
        pointerId: number;
        startX: number;
        scrollLeft: number;
        moved: boolean;
    } | null>(null);
    const weeks = [addDaysToKey(visibleWeek, -7), visibleWeek];

    if (visibleWeek < currentWeek) {
        weeks.push(addDaysToKey(visibleWeek, 7));
    }

    useLayoutEffect(() => {
        setVisibleWeek(selectedWeek > currentWeek ? currentWeek : selectedWeek);
    }, [date, selectedWeek, currentWeek]);

    useLayoutEffect(() => {
        const strip = stripRef.current;

        if (!strip) {
            return;
        }

        clearTimeout(settleTimer.current);
        strip.scrollTo({ left: strip.clientWidth, behavior: 'instant' });

        if (strip.contains(document.activeElement)) {
            strip.focus({ preventScroll: true });
        }

        return () => clearTimeout(settleTimer.current);
    }, [visibleWeek]);

    useLayoutEffect(() => {
        const strip = stripRef.current;

        if (!strip) {
            return;
        }

        let width = strip.clientWidth;
        const observer = new ResizeObserver(() => {
            if (strip.clientWidth !== width) {
                width = strip.clientWidth;
                clearTimeout(settleTimer.current);
                strip.scrollTo({ left: width, behavior: 'instant' });
            }
        });

        observer.observe(strip);

        return () => observer.disconnect();
    }, []);

    function finishScroll() {
        const strip = stripRef.current;
        clearTimeout(settleTimer.current);

        if (!strip || !strip.clientWidth || drag.current) {
            return;
        }

        const offset = Math.round(strip.scrollLeft / strip.clientWidth) - 1;

        if (offset !== 0) {
            const nextWeek = addDaysToKey(visibleWeek, offset * 7);
            setVisibleWeek(nextWeek > currentWeek ? currentWeek : nextWeek);
        }
    }

    function scrollToWeek(offset: number) {
        const strip = stripRef.current;

        if (!strip) {
            return;
        }

        strip.scrollTo({
            left:
                Math.max(0, Math.min(weeks.length - 1, 1 + offset)) *
                strip.clientWidth,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches
                ? 'instant'
                : 'smooth',
        });
    }

    function onKeyDown(event: KeyboardEvent<HTMLDivElement>) {
        suppressClick.current = false;

        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            scrollToWeek(event.key === 'ArrowLeft' ? -1 : 1);
        }
    }

    function onPointerDown(event: PointerEvent<HTMLDivElement>) {
        suppressClick.current = false;

        if (event.pointerType !== 'mouse' || event.button !== 0) {
            return;
        }

        drag.current = {
            pointerId: event.pointerId,
            startX: event.clientX,
            scrollLeft: event.currentTarget.scrollLeft,
            moved: false,
        };
    }

    function onPointerMove(event: PointerEvent<HTMLDivElement>) {
        const gesture = drag.current;

        if (!gesture || gesture.pointerId !== event.pointerId) {
            return;
        }

        const distance = event.clientX - gesture.startX;

        if (!gesture.moved && Math.abs(distance) < 8) {
            return;
        }

        gesture.moved = true;
        event.preventDefault();
        event.currentTarget.setPointerCapture(event.pointerId);
        event.currentTarget.style.scrollSnapType = 'none';
        event.currentTarget.scrollLeft = gesture.scrollLeft - distance;
    }

    function onPointerUp(event: PointerEvent<HTMLDivElement>) {
        const gesture = drag.current;

        if (!gesture || gesture.pointerId !== event.pointerId) {
            return;
        }

        drag.current = null;

        if (!gesture.moved) {
            return;
        }

        suppressClick.current = true;
        const distance = gesture.scrollLeft - event.currentTarget.scrollLeft;
        const offset =
            Math.abs(distance) >=
            Math.min(48, event.currentTarget.clientWidth / 5)
                ? -Math.sign(distance)
                : 0;
        event.currentTarget.style.scrollSnapType = '';

        if (event.currentTarget.hasPointerCapture(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
        }

        scrollToWeek(offset);
    }

    return {
        visibleWeek,
        weeks,
        showWeek: (day: string) => {
            const week = startOfWeekKey(day);
            setVisibleWeek(week > currentWeek ? currentWeek : week);
        },
        stripProps: {
            ref: stripRef,
            onKeyDown,
            onPointerDown,
            onPointerMove,
            onPointerUp,
            onPointerLeave: () => {
                if (!drag.current?.moved) {
                    drag.current = null;
                }
            },
            onPointerCancel: onPointerUp,
            onLostPointerCapture: onPointerUp,
            onScroll: () => {
                clearTimeout(settleTimer.current);
                settleTimer.current = setTimeout(finishScroll, 150);
            },
            onScrollEnd: finishScroll,
            onDragStart: (event: DragEvent<HTMLDivElement>) =>
                event.preventDefault(),
            onClickCapture: (event: MouseEvent<HTMLDivElement>) => {
                if (suppressClick.current) {
                    event.preventDefault();
                    event.stopPropagation();
                    suppressClick.current = false;
                }
            },
        },
    };
}
