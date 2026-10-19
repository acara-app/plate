import StoreWaterIntakeController from '@/actions/App/Http/Controllers/HealthEntry/StoreWaterIntakeController';
import InputError from '@/components/input-error';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { DailyWellness } from '@/types/diabetes';
import { router } from '@inertiajs/react';
import { GlassWater } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { BOTTOM_SHEET_CONTENT_CLASS } from './bottom-sheet';
import { formatAmount } from './macros';
import { MAX_WATER_ML, WATER_CUP_ML, WATER_TONE } from './wellness';

export default function WaterSheet({
    open,
    onClose,
    date,
    wellness,
}: {
    open: boolean;
    onClose: () => void;
    date: string;
    wellness: DailyWellness;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const [error, setError] = useState<string>();
    const filledCups = Math.floor(wellness.water_ml / WATER_CUP_ML);
    const cupCount = Math.min(
        MAX_WATER_ML / WATER_CUP_ML,
        Math.max(wellness.water_goal_ml / WATER_CUP_ML, filledCups + 1),
    );
    const cups = Array.from(
        { length: cupCount },
        (_, index) => (index + 1) * WATER_CUP_ML,
    );

    const pour = (cupMl: number) => {
        const target =
            cupMl === filledCups * WATER_CUP_ML ? cupMl - WATER_CUP_ML : cupMl;
        const amount = Math.max(0, target - wellness.water_synced_ml);

        router.post(
            StoreWaterIntakeController.url(),
            { date, amount_ml: amount },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['wellness'],
                optimistic: () => ({
                    wellness: {
                        ...wellness,
                        water_ml: wellness.water_synced_ml + amount,
                    },
                }),
                onSuccess: () => setError(undefined),
                onError: (errors) => setError(Object.values(errors)[0]),
            },
        );
    };

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent
                side="bottom"
                closeLabel={t('health_entries.timeline.close')}
                className={BOTTOM_SHEET_CONTENT_CLASS}
            >
                <SheetHeader className="px-5 pt-6 pr-16 pb-4">
                    <SheetTitle className="text-2xl font-bold tracking-tight">
                        {t('health_entries.trackers.water_heading')}
                    </SheetTitle>
                </SheetHeader>
                <div className="flex flex-col items-center gap-1 px-6 pt-4 text-center">
                    <p
                        className={cn(
                            'text-5xl font-bold tracking-tight tabular-nums',
                            WATER_TONE.icon,
                        )}
                    >
                        {formatAmount(wellness.water_ml, locale)} /{' '}
                        {formatAmount(wellness.water_goal_ml, locale)}
                    </p>
                    <p className="text-base text-muted-foreground">
                        {t('health_entries.trackers.ml_unit')}
                    </p>
                    {wellness.water_synced_ml > 0 && (
                        <p className="text-sm text-muted-foreground">
                            {t('health_entries.trackers.water_synced', {
                                value: formatAmount(
                                    wellness.water_synced_ml,
                                    locale,
                                ),
                            })}
                        </p>
                    )}
                </div>
                <div className="grid grid-cols-4 gap-3 px-5 pt-8">
                    {cups.map((cupMl) => {
                        const filled = cupMl <= wellness.water_ml;

                        return (
                            <button
                                key={cupMl}
                                type="button"
                                aria-pressed={filled}
                                aria-label={t(
                                    'health_entries.trackers.cup_label',
                                    { value: formatAmount(cupMl, locale) },
                                )}
                                onClick={() => pour(cupMl)}
                                className={cn(
                                    'flex aspect-[4/5] min-w-0 flex-col items-center justify-center gap-2 rounded-2xl ring-1 transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-primary/50',
                                    filled
                                        ? WATER_TONE.cup
                                        : 'bg-muted text-muted-foreground ring-border/60 hover:bg-accent',
                                )}
                            >
                                <GlassWater
                                    className="size-8"
                                    aria-hidden="true"
                                />
                                <span className="text-sm font-medium tabular-nums">
                                    {formatAmount(cupMl, locale)}
                                </span>
                            </button>
                        );
                    })}
                </div>
                <InputError message={error} className="mt-4 px-5 text-center" />
                <SheetDescription className="px-6 pt-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] text-center text-sm">
                    {t('health_entries.trackers.water_hint', {
                        value: formatAmount(WATER_CUP_ML, locale),
                    })}
                </SheetDescription>
            </SheetContent>
        </Sheet>
    );
}
