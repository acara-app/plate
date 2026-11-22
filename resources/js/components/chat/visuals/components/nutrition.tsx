import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';
import type { ViewProps } from './props';
import { useVisualFormat } from './use-visual-format';

const MACROS = [
    { key: 'carbs', kcalPerGram: 4, color: 'bg-amber-500 dark:bg-amber-400' },
    {
        key: 'protein',
        kcalPerGram: 4,
        color: 'bg-emerald-600 dark:bg-emerald-400',
    },
    { key: 'fat', kcalPerGram: 9, color: 'bg-sky-600 dark:bg-sky-400' },
] as const;

const RISK = {
    low: {
        filled: 1,
        text: 'text-emerald-700 dark:text-emerald-400',
        fill: 'bg-emerald-600 dark:bg-emerald-400',
    },
    medium: {
        filled: 2,
        text: 'text-amber-700 dark:text-amber-400',
        fill: 'bg-amber-500 dark:bg-amber-400',
    },
    high: {
        filled: 3,
        text: 'text-red-700 dark:text-red-400',
        fill: 'bg-red-600 dark:bg-red-400',
    },
} as const;

export function MacroBreakdown({ props }: ViewProps<'MacroBreakdown'>) {
    const format = useVisualFormat();
    const energy = MACROS.map((macro) => props[macro.key] * macro.kcalPerGram);
    const total = energy.reduce((sum, value) => sum + value, 0);
    const nutrients = [
        ...MACROS.map((macro) => ({
            key: macro.key,
            grams: props[macro.key],
            color: macro.color,
        })),
        ...(props.fiber === null || props.fiber === undefined
            ? []
            : [
                  {
                      key: 'fiber',
                      grams: props.fiber,
                      color: 'bg-foreground/25',
                  },
              ]),
    ];

    return (
        <div className="flex flex-col gap-3">
            <p className="flex items-baseline gap-1.5">
                <span className="text-2xl font-semibold tracking-tight text-foreground tabular-nums">
                    {format.number(props.calories)}
                </span>
                <span className="text-sm text-muted-foreground">
                    {format.unit('kcal')}
                </span>
            </p>
            {total > 0 && (
                <div
                    className="flex h-2 gap-0.5 overflow-hidden rounded-full"
                    aria-hidden="true"
                >
                    {MACROS.map((macro, index) =>
                        energy[index] > 0 ? (
                            <span
                                key={macro.key}
                                className={cn('h-full', macro.color)}
                                style={{
                                    width: `${(energy[index] / total) * 100}%`,
                                }}
                            />
                        ) : null,
                    )}
                </div>
            )}
            <dl
                className={cn(
                    'grid gap-3',
                    nutrients.length > 3
                        ? 'grid-cols-2 @min-[24rem]:grid-cols-4'
                        : 'grid-cols-3',
                )}
            >
                {nutrients.map((nutrient) => (
                    <div key={nutrient.key} className="flex min-w-0 flex-col">
                        <dt className="flex items-center gap-1.5 text-[13px] leading-5 text-muted-foreground">
                            <span
                                className={cn(
                                    'size-2 shrink-0 rounded-full',
                                    nutrient.color,
                                )}
                                aria-hidden="true"
                            />
                            {format.label(`nutrients.${nutrient.key}`)}
                        </dt>
                        <dd className="text-[15px] leading-6 font-medium text-foreground tabular-nums">
                            {format.number(nutrient.grams)} {format.unit('g')}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}

export function RiskMeter({ props }: ViewProps<'RiskMeter'>) {
    const { t } = useTranslation('visuals');
    const level = RISK[props.level];

    return (
        <div className="flex flex-col gap-2">
            <div className="flex items-baseline justify-between gap-3">
                <p className="text-[15px] leading-6 font-semibold text-balance text-foreground">
                    {props.label}
                </p>
                <span
                    className={cn(
                        'shrink-0 text-[13px] leading-5 font-medium',
                        level.text,
                    )}
                >
                    {t(`risk.${props.level}`)}
                </span>
            </div>
            <div className="grid grid-cols-3 gap-1" aria-hidden="true">
                {[1, 2, 3].map((segment) => (
                    <span
                        key={segment}
                        className={cn(
                            'h-1.5 rounded-full',
                            segment <= level.filled
                                ? level.fill
                                : 'bg-foreground/10',
                        )}
                    />
                ))}
            </div>
            {props.detail && (
                <p className="text-sm leading-6 text-muted-foreground">
                    {props.detail}
                </p>
            )}
        </div>
    );
}
