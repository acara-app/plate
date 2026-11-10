import { cn } from '@/lib/utils';
import {
    CalendarDays,
    Droplet,
    Dumbbell,
    EggFried,
    GlassWater,
    MoonStar,
    Salad,
    Soup,
    type LucideIcon,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { DayPart } from './day-part';

export type StarterId =
    | 'breakfast'
    | 'lunch'
    | 'dinner'
    | 'tomorrow'
    | 'glucose'
    | 'water'
    | 'workout'
    | 'sleep';

const MEAL_TINT =
    'bg-orange-100 text-orange-700 dark:bg-orange-400/15 dark:text-orange-300';

export const STARTERS: Record<StarterId, { Icon: LucideIcon; tint: string }> = {
    breakfast: { Icon: EggFried, tint: MEAL_TINT },
    lunch: { Icon: Salad, tint: MEAL_TINT },
    dinner: { Icon: Soup, tint: MEAL_TINT },
    tomorrow: { Icon: CalendarDays, tint: MEAL_TINT },
    glucose: {
        Icon: Droplet,
        tint: 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300',
    },
    water: {
        Icon: GlassWater,
        tint: 'bg-sky-100 text-sky-700 dark:bg-sky-400/15 dark:text-sky-300',
    },
    workout: {
        Icon: Dumbbell,
        tint: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
    },
    sleep: {
        Icon: MoonStar,
        tint: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-300',
    },
};

const LINEUPS: Record<DayPart, StarterId[]> = {
    morning: ['breakfast', 'glucose', 'workout', 'sleep'],
    midday: ['lunch', 'glucose', 'workout', 'sleep'],
    evening: ['dinner', 'glucose', 'workout', 'sleep'],
    night: ['sleep', 'tomorrow', 'glucose', 'workout'],
};

const STAGGER = [
    '[animation-delay:260ms]',
    '[animation-delay:320ms]',
    '[animation-delay:380ms]',
    '[animation-delay:440ms]',
];

const PRESSABLE =
    'cursor-pointer touch-manipulation select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400';

const VARIANTS = {
    row: {
        list: 'no-scrollbar flex snap-x gap-2 overflow-x-auto overscroll-x-contain pb-2.5 @min-[28rem]:flex-wrap @min-[28rem]:overflow-visible',
        item: 'shrink-0 snap-start',
        button: 'inline-flex h-11 items-center gap-2 rounded-full bg-muted/70 pr-4 pl-1.5 text-[15px] font-medium whitespace-nowrap text-foreground/90 transition-[scale,background-color] duration-150 ease-out hover:bg-muted active:scale-[0.97] active:bg-muted dark:bg-muted/60',
        icon: 'size-8',
    },
    grid: {
        list: 'grid grid-cols-4 gap-2.5',
        item: 'flex',
        button: 'flex w-full flex-col items-start gap-2.5 rounded-3xl bg-foreground/[0.04] p-3.5 text-left text-[15px] leading-5 font-semibold text-foreground transition-[translate,scale,background-color] duration-200 ease-out hover:-translate-y-0.5 hover:bg-foreground/[0.07] active:translate-y-0 active:scale-[0.98] dark:bg-white/[0.06] dark:hover:bg-white/10',
        icon: 'size-9',
    },
} as const;

export function ChatStarters({
    dayPart,
    onPick,
    variant,
    className,
}: {
    dayPart: DayPart;
    onPick: (prompt: string) => void;
    variant: keyof typeof VARIANTS;
    className?: string;
}) {
    const { t } = useTranslation('common');
    const styles = VARIANTS[variant];

    return (
        <ul
            aria-label={t('chat.starters.label')}
            className={cn(styles.list, className)}
        >
            {LINEUPS[dayPart].map((id, index) => {
                const { Icon, tint } = STARTERS[id];

                return (
                    <li
                        key={id}
                        className={cn(
                            'duration-500 ease-out fill-mode-both motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2',
                            styles.item,
                            STAGGER[index],
                        )}
                    >
                        <button
                            type="button"
                            data-test="chat-starter"
                            onClick={() =>
                                onPick(t(`chat.starters.${id}.prompt`))
                            }
                            className={cn(PRESSABLE, styles.button)}
                        >
                            <span
                                className={cn(
                                    'flex shrink-0 items-center justify-center rounded-full',
                                    styles.icon,
                                    tint,
                                )}
                            >
                                <Icon
                                    className="size-4"
                                    strokeWidth={2}
                                    aria-hidden="true"
                                />
                            </span>
                            {t(`chat.starters.${id}.label`)}
                        </button>
                    </li>
                );
            })}
        </ul>
    );
}
