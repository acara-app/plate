import StoreDailyCheckInController from '@/actions/App/Http/Controllers/HealthEntry/StoreDailyCheckInController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type {
    DailyWellness,
    SleepQuality,
    StressLevel,
} from '@/types/diabetes';
import { useForm } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { formatAmount } from './macros';
import {
    CHECK_IN_STEPS,
    DEFAULT_SLEEP_HOURS,
    MAX_SLEEP_HOURS,
    SLEEP_QUALITY_OPTIONS,
    STRESS_OPTIONS,
    type CheckInOption,
    type CheckInStep,
} from './wellness';

interface CheckInForm {
    date: string;
    sleep_hours: number;
    sleep_quality: SleepQuality | null;
    stress_level: StressLevel | null;
}

const HOUR_MARKS = [0, 4, 8, 12];

export default function CheckInSheet({
    open,
    onClose,
    date,
    wellness,
    startStep,
}: {
    open: boolean;
    onClose: () => void;
    date: string;
    wellness: DailyWellness;
    startStep: CheckInStep;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const steps: CheckInStep[] = wellness.sleep_synced
        ? ['quality', 'stress']
        : ['hours', 'quality', 'stress'];
    const [index, setIndex] = useState(() =>
        Math.max(0, steps.indexOf(startStep)),
    );
    const [answered, setAnswered] = useState<Set<CheckInStep>>(new Set());
    const form = useForm<CheckInForm>({
        date,
        sleep_hours: Math.min(
            wellness.sleep_hours ?? DEFAULT_SLEEP_HOURS,
            MAX_SLEEP_HOURS,
        ),
        sleep_quality: wellness.sleep_quality,
        stress_level: wellness.stress_level,
    });

    const step = steps[index];
    const config = CHECK_IN_STEPS[step];
    const isLast = index === steps.length - 1;
    const hasAnswer =
        step === 'hours' ||
        (step === 'quality' && form.data.sleep_quality !== null) ||
        (step === 'stress' && form.data.stress_level !== null);
    const error = Object.values(form.errors)[0];

    const submit = (finalAnswers: Set<CheckInStep>) => {
        if (finalAnswers.size === 0) {
            onClose();

            return;
        }

        form.transform((data) => ({
            date: data.date,
            sleep_hours: finalAnswers.has('hours') ? data.sleep_hours : null,
            sleep_quality: finalAnswers.has('quality')
                ? data.sleep_quality
                : null,
            stress_level: finalAnswers.has('stress') ? data.stress_level : null,
        }));
        form.post(StoreDailyCheckInController.url(), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const advance = (isAnswered: boolean) => {
        const nextAnswers = new Set(answered);

        if (isAnswered) {
            nextAnswers.add(step);
        } else {
            nextAnswers.delete(step);
        }

        setAnswered(nextAnswers);

        if (isLast) {
            submit(nextAnswers);
        } else {
            setIndex(index + 1);
        }
    };

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent
                side="bottom"
                closeLabel={t('health_entries.timeline.close')}
                className="mx-auto max-h-[90dvh] w-full max-w-xl gap-0 overflow-y-auto overscroll-contain rounded-t-3xl border-x p-0 [&>button:last-child]:top-3 [&>button:last-child]:right-3 [&>button:last-child]:flex [&>button:last-child]:size-11 [&>button:last-child]:items-center [&>button:last-child]:justify-center [&>button:last-child]:rounded-full"
            >
                <div className="flex items-center gap-2 px-3 pt-3 pr-16">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className={cn(
                            'size-11 shrink-0 rounded-full',
                            index === 0 && 'invisible',
                        )}
                        onClick={() => setIndex(index - 1)}
                        aria-label={t('health_entries.wellness.back')}
                    >
                        <ChevronLeft className="size-6" aria-hidden="true" />
                    </Button>
                    <div
                        role="progressbar"
                        aria-label={t('health_entries.wellness.progress', {
                            current: index + 1,
                            total: steps.length,
                        })}
                        aria-valuemin={1}
                        aria-valuemax={steps.length}
                        aria-valuenow={index + 1}
                        className="flex flex-1 gap-2"
                    >
                        {steps.map((item, itemIndex) => (
                            <span
                                key={item}
                                className={cn(
                                    'h-1.5 flex-1 rounded-full transition-colors',
                                    itemIndex <= index
                                        ? 'bg-primary'
                                        : 'bg-muted',
                                )}
                            />
                        ))}
                    </div>
                </div>
                <div className="flex flex-col items-center gap-2 px-6 pt-8 text-center">
                    <span
                        aria-hidden="true"
                        className={cn(
                            'mb-4 flex size-20 items-center justify-center rounded-full',
                            config.tile,
                        )}
                    >
                        <config.Icon className={cn('size-9', config.icon)} />
                    </span>
                    <SheetTitle className="text-2xl font-bold tracking-tight">
                        {t(config.titleKey)}
                    </SheetTitle>
                    <SheetDescription className="text-base">
                        {t(config.descriptionKey)}
                    </SheetDescription>
                </div>
                <div className="flex min-h-64 flex-col justify-center px-6 py-8">
                    {step === 'hours' && (
                        <div className="flex flex-col items-center gap-8">
                            <p className="flex flex-col items-center">
                                <span className="text-6xl font-bold tracking-tight tabular-nums">
                                    {formatAmount(
                                        form.data.sleep_hours,
                                        locale,
                                    )}
                                </span>
                                <span className="text-lg text-muted-foreground">
                                    {t('health_entries.wellness.hours_unit')}
                                </span>
                            </p>
                            <div className="flex w-full max-w-sm flex-col gap-3">
                                <input
                                    type="range"
                                    min={0}
                                    max={MAX_SLEEP_HOURS}
                                    step={0.5}
                                    value={form.data.sleep_hours}
                                    onChange={(event) =>
                                        form.setData(
                                            'sleep_hours',
                                            Number(event.target.value),
                                        )
                                    }
                                    aria-label={t(config.titleKey)}
                                    aria-valuetext={t(
                                        'health_entries.wellness.hours_short',
                                        {
                                            value: formatAmount(
                                                form.data.sleep_hours,
                                                locale,
                                            ),
                                        },
                                    )}
                                    style={{
                                        background: `linear-gradient(to right, var(--primary) ${(form.data.sleep_hours / MAX_SLEEP_HOURS) * 100}%, var(--muted) 0)`,
                                    }}
                                    className="h-1.5 w-full cursor-pointer appearance-none rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-primary/50 [&::-moz-range-thumb]:size-8 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:shadow-md [&::-webkit-slider-thumb]:size-8 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:ring-1 [&::-webkit-slider-thumb]:ring-black/10"
                                />
                                <div
                                    aria-hidden="true"
                                    className="flex justify-between text-sm text-muted-foreground tabular-nums"
                                >
                                    {HOUR_MARKS.map((mark) => (
                                        <span key={mark}>
                                            {t(
                                                'health_entries.wellness.hours_short',
                                                { value: mark },
                                            )}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                    {step === 'quality' && (
                        <ChoiceGrid
                            options={SLEEP_QUALITY_OPTIONS}
                            selected={form.data.sleep_quality}
                            onSelect={(value) =>
                                form.setData('sleep_quality', value)
                            }
                        />
                    )}
                    {step === 'stress' && (
                        <ChoiceGrid
                            options={STRESS_OPTIONS}
                            selected={form.data.stress_level}
                            onSelect={(value) =>
                                form.setData('stress_level', value)
                            }
                        />
                    )}
                    <InputError message={error} className="mt-4 text-center" />
                </div>
                <div className="grid grid-cols-2 gap-3 px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
                    <Button
                        type="button"
                        variant="outline"
                        className="h-12 rounded-2xl text-base"
                        disabled={form.processing}
                        onClick={() => advance(false)}
                    >
                        {t('health_entries.wellness.skip')}
                    </Button>
                    <Button
                        type="button"
                        className="h-12 rounded-2xl text-base"
                        disabled={!hasAnswer || form.processing}
                        onClick={() => advance(true)}
                    >
                        {isLast ? (
                            <>
                                {t('health_entries.wellness.save')}
                                <Check aria-hidden="true" />
                            </>
                        ) : (
                            <>
                                {t('health_entries.wellness.continue')}
                                <ChevronRight aria-hidden="true" />
                            </>
                        )}
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}

function ChoiceGrid<T extends number>({
    options,
    selected,
    onSelect,
}: {
    options: CheckInOption<T>[];
    selected: T | null;
    onSelect: (value: T) => void;
}) {
    const { t } = useTranslation('common');

    return (
        <div className="grid grid-cols-3 gap-3">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    aria-pressed={selected === option.value}
                    onClick={() => onSelect(option.value)}
                    className={cn(
                        'flex min-w-0 flex-col items-center gap-3 rounded-3xl bg-card px-2 py-5 shadow-xs ring-1 ring-border/60 transition outline-none focus-visible:ring-[3px] focus-visible:ring-primary/50',
                        selected === option.value && 'ring-2 ring-primary',
                    )}
                >
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-14 items-center justify-center rounded-full',
                            option.tile,
                        )}
                    >
                        <option.Icon className={cn('size-7', option.icon)} />
                    </span>
                    <span className="truncate text-sm font-medium">
                        {t(option.labelKey)}
                    </span>
                </button>
            ))}
        </div>
    );
}
