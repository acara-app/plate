import {
    AUTH_HEADLINE,
    AUTH_LEAD,
    AUTH_PRESSABLE,
    AuthFieldError,
    AuthScreen,
    AuthSubmitButton,
} from '@/components/auth/auth-screen';
import { DISPLAY_FONT } from '@/components/dashboard/intake-plate';
import { MeasurementRuler } from '@/components/onboarding/measurement-ruler';
import { OnboardingHeader } from '@/components/onboarding/onboarding-shell';
import useSharedProps from '@/hooks/use-shared-props';
import {
    centimetersToFeetAndInches,
    exactFeetAndInches,
    exactPounds,
    feetAndInchesToCentimeters,
    formatMeasurement,
    kilogramsToPounds,
    type MeasurementSystem,
    parseMeasurement,
    poundsToKilograms,
} from '@/lib/body-measurements';
import { cn } from '@/lib/utils';
import onboarding from '@/routes/onboarding';
import { BloodTypeOption, Profile, SexOption } from '@/types/user';
import { Form } from '@inertiajs/react';
import { Info, Pencil, Scale } from 'lucide-react';
import { type ReactNode, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Measurements {
    weight: string;
    heightCm: string;
    heightFeet: string;
    heightInches: string;
    exactWeightKg: number | null;
    exactHeightCm: number | null;
}

type MeasurementField = 'weight' | 'heightCm' | 'heightFeet' | 'heightInches';

const MEASUREMENT_PROMPTS = ['weight_in_pounds', 'measurements'];

const MEASUREMENT_ERRORS = [
    ...MEASUREMENT_PROMPTS,
    'weight',
    'height',
    'height_feet',
    'height_inches',
];

const EASE = 'ease-[cubic-bezier(0.23,1,0.32,1)]';

const RISE = cn(
    'duration-300 fill-mode-both motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2',
    EASE,
);

const SURFACE = 'bg-foreground/[0.045] dark:bg-white/[0.06]';

const NUMBER = cn(
    'min-w-0 rounded-xl bg-transparent text-right text-[2rem] leading-none font-extrabold tracking-[-0.03em] text-foreground tabular-nums caret-emerald-600 transition-colors duration-150 ease-out outline-none placeholder:text-foreground/25 hover:bg-foreground/[0.05] focus-visible:bg-foreground/[0.05] dark:hover:bg-white/[0.07] dark:focus-visible:bg-white/[0.07]',
    DISPLAY_FONT,
);

const UNIT = 'text-[1.0625rem] font-semibold text-muted-foreground';

const SEGMENT_THUMB = cn(
    'absolute rounded-full bg-background shadow-[0_1px_2px_rgb(0_0_0/0.08),0_4px_10px_-4px_rgb(0_0_0/0.18)] duration-200 motion-reduce:transition-none dark:bg-neutral-700',
    EASE,
);

const NOTICE_BUTTON = cn(
    'min-h-11 rounded-full px-4 py-2 text-sm font-semibold transition-[scale] duration-150 ease-out active:scale-[0.97]',
    AUTH_PRESSABLE,
);

function initialMeasurements(
    profile: Profile | null,
    measurementSystem: MeasurementSystem,
): Measurements {
    const weight = profile?.weight?.toString() ?? '';
    const heightCm = profile?.height?.toString() ?? '';

    if (measurementSystem === 'metric') {
        return {
            weight,
            heightCm,
            heightFeet: '',
            heightInches: '',
            exactWeightKg: null,
            exactHeightCm: null,
        };
    }

    const { feet, inches } = centimetersToFeetAndInches(heightCm);

    return {
        weight: kilogramsToPounds(weight),
        heightCm: '',
        heightFeet: feet,
        heightInches: inches,
        exactWeightKg: parseMeasurement(weight),
        exactHeightCm: parseMeasurement(heightCm),
    };
}

function heightInFeetAndInches(heightCm: number | null) {
    if (heightCm === null) {
        return { heightFeet: '', heightInches: '' };
    }

    const { feet, inches } = centimetersToFeetAndInches(String(heightCm));

    return { heightFeet: feet, heightInches: inches };
}

function isValidMeasurement(
    value: string,
    minimum: number,
    maximum: number,
): boolean {
    const measurement = Number(value);

    return (
        value.trim() !== '' &&
        Number.isFinite(measurement) &&
        measurement >= minimum &&
        measurement <= maximum
    );
}

function typed(value: string): string {
    return value.replace(',', '.');
}

function Dial({
    order,
    label,
    error,
    hint,
    readout,
    className,
    children,
}: {
    order: number;
    label: string;
    error?: string | false;
    hint?: ReactNode;
    readout: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            style={{ animationDelay: `${order * 45}ms` }}
            className={cn(
                'min-w-0 rounded-[1.75rem] px-5 pt-4 pb-3',
                SURFACE,
                error && 'ring-2 ring-rose-500/70',
                RISE,
                className,
            )}
        >
            <div className="flex items-end justify-between gap-4">
                <h2 className="pb-1 text-[0.9375rem] font-semibold text-muted-foreground">
                    {label}
                </h2>
                <div className="flex min-w-0 items-baseline justify-end gap-1.5">
                    {readout}
                </div>
            </div>
            {hint}
            <div className="mt-2">{children}</div>
            <AuthFieldError message={error} className="pt-2" />
        </section>
    );
}

function Notice({
    tone,
    title,
    children,
}: {
    tone: 'info' | 'check';
    title: string;
    children: ReactNode;
}) {
    const Icon = tone === 'info' ? Info : Scale;

    return (
        <div
            role={tone === 'info' ? 'status' : 'alert'}
            className={cn(
                'rounded-3xl p-4 duration-200 ease-out motion-safe:animate-in motion-safe:fade-in',
                tone === 'info'
                    ? 'bg-emerald-500/10'
                    : 'bg-amber-400/15 dark:bg-amber-400/10',
            )}
        >
            <p className="flex items-start gap-2 text-[0.9375rem] font-bold text-foreground">
                <Icon
                    className={cn(
                        'mt-0.5 size-4 shrink-0',
                        tone === 'info'
                            ? 'text-emerald-700 dark:text-emerald-400'
                            : 'text-amber-600 dark:text-amber-400',
                    )}
                    aria-hidden="true"
                />
                {title}
            </p>
            <div className="mt-1 grid gap-3 text-sm leading-snug text-muted-foreground">
                {children}
            </div>
        </div>
    );
}

interface Props {
    profile: Profile | null;
    currentAge: number | null;
    weightLooksLikePounds: boolean;
    sexOptions: SexOption[];
    bloodTypeOptions: BloodTypeOption[];
}

export default function Biometrics({
    profile,
    currentAge,
    weightLooksLikePounds,
    sexOptions,
    bloodTypeOptions,
}: Props) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();
    const needsReview = profile?.needs_re_onboarding === true;
    const isCompletedReview =
        needsReview && profile.onboarding_completed === true;
    const [name, setName] = useState(currentUser?.name ?? '');
    const [isEditingName, setIsEditingName] = useState(!currentUser?.name);
    const [age, setAge] = useState<string>(currentAge?.toString() ?? '');
    const [sex, setSex] = useState<string>(profile?.sex ?? '');
    const [bloodType, setBloodType] = useState<string>(
        profile?.blood_type ?? '',
    );
    const [measurementSystem, setMeasurementSystem] =
        useState<MeasurementSystem>(
            () =>
                profile?.measurement_system ??
                (needsReview ? 'metric' : 'imperial'),
        );
    const [measurements, setMeasurements] = useState<Measurements>(() =>
        initialMeasurements(profile, measurementSystem),
    );
    const [showProblem, setShowProblem] = useState(false);
    const confirmMeasurementsRef = useRef(false);
    const isImperial = measurementSystem === 'imperial';
    const unit = (key: string) => t(`onboarding.biometrics.unit_${key}`);

    const heightFeet = parseMeasurement(measurements.heightFeet);
    const totalInches =
        heightFeet === null
            ? null
            : heightFeet * 12 +
              Math.round(parseMeasurement(measurements.heightInches) ?? 0);
    const weightLabel = `${measurements.weight} ${unit(isImperial ? 'lb' : 'kg')}`;
    const heightLabel = isImperial
        ? `${measurements.heightFeet || 0} ${unit('ft')} ${measurements.heightInches || 0} ${unit('in')}`
        : `${measurements.heightCm} ${unit('cm')}`;

    const nameReady = isCompletedReview || name.trim() !== '';
    const ageReady =
        isValidMeasurement(age, 13, 120) && Number.isInteger(Number(age));
    const sexReady = sex !== '';
    const heightReady = isImperial
        ? isValidMeasurement(measurements.heightFeet, 1, 9) &&
          Number.isInteger(Number(measurements.heightFeet)) &&
          (measurements.heightInches.trim() === '' ||
              isValidMeasurement(measurements.heightInches, 0, 11.9))
        : isValidMeasurement(measurements.heightCm, 50, 300);
    const weightReady = isValidMeasurement(
        measurements.weight,
        isImperial ? 45 : 20,
        isImperial ? 1102 : 500,
    );
    const ready =
        nameReady && ageReady && sexReady && heightReady && weightReady;
    const missing = (isReady: boolean, key: string) =>
        showProblem && !isReady && t(`onboarding.biometrics.${key}_missing`);

    const weightPreview =
        isImperial && measurements.exactWeightKg !== null
            ? `${formatMeasurement(measurements.exactWeightKg, 2)} ${unit('kg')}`
            : weightReady
              ? `${isImperial ? poundsToKilograms(measurements.weight) : kilogramsToPounds(measurements.weight)} ${unit(isImperial ? 'kg' : 'lb')}`
              : null;
    const metricHeightPreview = centimetersToFeetAndInches(
        measurements.heightCm,
    );
    const heightPreview = isImperial
        ? measurements.exactHeightCm !== null
            ? `${formatMeasurement(measurements.exactHeightCm, 2)} ${unit('cm')}`
            : heightReady
              ? `${feetAndInchesToCentimeters(measurements.heightFeet, measurements.heightInches)} ${unit('cm')}`
              : null
        : heightReady
          ? `${metricHeightPreview.feet} ${unit('ft')} ${metricHeightPreview.inches} ${unit('in')}`
          : null;
    const preview = (id: string, measurement: string | null) =>
        measurement && (
            <p
                id={id}
                className="mt-1 text-right text-[0.8125rem] text-muted-foreground"
            >
                {t('onboarding.biometrics.conversion_preview', {
                    measurement,
                })}
            </p>
        );

    const submission = () => {
        const exactHeight =
            measurements.exactHeightCm === null
                ? null
                : exactFeetAndInches(measurements.exactHeightCm);

        return {
            ...(isCompletedReview ? {} : { name: name.trim() }),
            age,
            sex,
            blood_type: bloodType,
            measurement_system: measurementSystem,
            ...(isImperial
                ? {
                      height_feet: exactHeight?.feet ?? measurements.heightFeet,
                      height_inches:
                          exactHeight?.inches ?? measurements.heightInches,
                      weight:
                          measurements.exactWeightKg === null
                              ? measurements.weight
                              : exactPounds(measurements.exactWeightKg),
                  }
                : {
                      height: measurements.heightCm,
                      weight: measurements.weight,
                  }),
            ...(confirmMeasurementsRef.current
                ? { confirm_measurements: true }
                : {}),
        };
    };

    return (
        <AuthScreen
            title={t(
                isCompletedReview
                    ? 'onboarding.biometrics.review_heading'
                    : 'onboarding.biometrics.title',
            )}
            className="md:min-h-0 md:max-w-[54rem]"
        >
            <Form
                {...onboarding.biometrics.store.form()}
                noValidate
                transform={submission}
                onBefore={() => {
                    if (!ready) {
                        setShowProblem(true);

                        return false;
                    }
                }}
                onFinish={() => {
                    confirmMeasurementsRef.current = false;
                }}
                disableWhileProcessing
                className="flex flex-1 flex-col"
            >
                {({ processing, errors, clearErrors, submit }) => {
                    const clearMeasurementErrors = (
                        keys = MEASUREMENT_ERRORS,
                    ) => {
                        const present = keys.filter(
                            (key) => errors[key as keyof typeof errors],
                        );

                        if (present.length > 0) {
                            clearErrors(...present);
                        }
                    };

                    const changeMeasurement = (
                        field: MeasurementField,
                        value: string,
                    ) => {
                        setMeasurements((current) => ({
                            ...current,
                            [field]: value,
                            ...(field === 'weight'
                                ? { exactWeightKg: null }
                                : { exactHeightCm: null }),
                        }));
                        clearMeasurementErrors();
                    };

                    const changeHeightInches = (inches: number) => {
                        setMeasurements((current) => ({
                            ...current,
                            heightFeet: String(Math.floor(inches / 12)),
                            heightInches: String(inches % 12),
                            exactHeightCm: null,
                        }));
                        clearMeasurementErrors();
                    };

                    const switchMeasurementSystem = (
                        next: MeasurementSystem,
                    ) => {
                        if (next === measurementSystem) {
                            return;
                        }

                        setMeasurements((current) => {
                            if (next === 'imperial') {
                                const weightKg = parseMeasurement(
                                    current.weight,
                                );
                                const heightCm = parseMeasurement(
                                    current.heightCm,
                                );

                                return {
                                    ...current,
                                    weight:
                                        weightKg === null
                                            ? ''
                                            : kilogramsToPounds(
                                                  String(weightKg),
                                              ),
                                    ...heightInFeetAndInches(heightCm),
                                    exactWeightKg: weightKg,
                                    exactHeightCm: heightCm,
                                };
                            }

                            return {
                                ...current,
                                weight:
                                    current.exactWeightKg === null
                                        ? poundsToKilograms(current.weight)
                                        : formatMeasurement(
                                              current.exactWeightKg,
                                              2,
                                          ),
                                heightCm:
                                    current.exactHeightCm === null
                                        ? feetAndInchesToCentimeters(
                                              current.heightFeet,
                                              current.heightInches,
                                          )
                                        : formatMeasurement(
                                              current.exactHeightCm,
                                              2,
                                          ),
                                exactWeightKg: null,
                                exactHeightCm: null,
                            };
                        });
                        setMeasurementSystem(next);
                        clearMeasurementErrors();
                    };

                    const treatWeightAsPounds = () => {
                        setMeasurements((current) => {
                            const heightCm = parseMeasurement(current.heightCm);

                            return {
                                ...current,
                                ...heightInFeetAndInches(heightCm),
                                exactWeightKg: null,
                                exactHeightCm: heightCm,
                            };
                        });
                        setMeasurementSystem('imperial');
                        clearMeasurementErrors();
                    };

                    const confirmMeasurements = () => {
                        confirmMeasurementsRef.current = true;
                        submit();
                    };

                    const heightError =
                        errors.height ??
                        errors.height_feet ??
                        errors.height_inches;
                    const stepLabels = (label: string) => ({
                        label,
                        decreaseLabel: t('onboarding.biometrics.step_down', {
                            label,
                        }),
                        increaseLabel: t('onboarding.biometrics.step_up', {
                            label,
                        }),
                    });

                    return (
                        <>
                            <OnboardingHeader
                                step={isCompletedReview ? undefined : 1}
                            />

                            <div className="mt-7 grid flex-1 grid-cols-1 content-start gap-2.5 md:mt-10 md:grid-cols-2 md:items-start md:gap-x-10 md:gap-y-4">
                                <div
                                    className={cn(
                                        'pb-3 md:col-start-1 md:row-span-2 md:row-start-1 md:pb-0',
                                        RISE,
                                    )}
                                >
                                    {isCompletedReview ? (
                                        <>
                                            <h1 className={AUTH_HEADLINE}>
                                                {t(
                                                    'onboarding.biometrics.review_heading',
                                                )}
                                            </h1>
                                            <p className={AUTH_LEAD}>
                                                {t(
                                                    'onboarding.biometrics.review_body',
                                                )}
                                            </p>
                                        </>
                                    ) : (
                                        <>
                                            <h1
                                                className={cn(
                                                    AUTH_HEADLINE,
                                                    'flex flex-wrap items-center gap-x-2.5 gap-y-1',
                                                )}
                                            >
                                                {t(
                                                    'onboarding.biometrics.greeting',
                                                )}
                                                {isEditingName ? (
                                                    <input
                                                        id="name"
                                                        type="text"
                                                        name="name"
                                                        autoFocus={
                                                            !!currentUser?.name
                                                        }
                                                        autoComplete="given-name"
                                                        autoCapitalize="words"
                                                        autoCorrect="off"
                                                        enterKeyHint="done"
                                                        maxLength={255}
                                                        aria-label={t(
                                                            'onboarding.biometrics.name',
                                                        )}
                                                        placeholder={t(
                                                            'onboarding.biometrics.name_placeholder',
                                                        )}
                                                        value={name}
                                                        onChange={(event) => {
                                                            setName(
                                                                event.target
                                                                    .value,
                                                            );

                                                            if (errors.name) {
                                                                clearErrors(
                                                                    'name',
                                                                );
                                                            }
                                                        }}
                                                        onBlur={() =>
                                                            name.trim() !==
                                                                '' &&
                                                            setIsEditingName(
                                                                false,
                                                            )
                                                        }
                                                        onKeyDown={(event) => {
                                                            if (
                                                                event.key ===
                                                                'Enter'
                                                            ) {
                                                                event.preventDefault();
                                                                event.currentTarget.blur();
                                                            }
                                                        }}
                                                        aria-invalid={
                                                            !!errors.name ||
                                                            (showProblem &&
                                                                !nameReady)
                                                        }
                                                        style={{
                                                            width: `${Math.max(name.length, 9) + 1.5}ch`,
                                                        }}
                                                        className="max-w-full rounded-2xl bg-foreground/[0.06] px-3 py-1 font-extrabold tracking-[-0.035em] caret-emerald-600 outline-none placeholder:text-foreground/25 focus-visible:ring-2 focus-visible:ring-emerald-600 aria-invalid:ring-2 aria-invalid:ring-rose-500 dark:bg-white/[0.08]"
                                                    />
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setIsEditingName(
                                                                true,
                                                            )
                                                        }
                                                        className={cn(
                                                            '-mx-1 flex max-w-full items-center gap-2 rounded-2xl px-1 text-left font-extrabold tracking-[-0.035em] transition-[scale] duration-150 ease-out active:scale-[0.97]',
                                                            AUTH_PRESSABLE,
                                                        )}
                                                    >
                                                        <span className="truncate">
                                                            {name.trim()}
                                                        </span>
                                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-foreground/[0.07] dark:bg-white/10">
                                                            <Pencil
                                                                className="size-4 text-muted-foreground"
                                                                aria-hidden="true"
                                                            />
                                                        </span>
                                                        <span className="sr-only">
                                                            {t(
                                                                'onboarding.biometrics.name_change',
                                                            )}
                                                        </span>
                                                    </button>
                                                )}
                                            </h1>
                                            <p className={AUTH_LEAD}>
                                                {t(
                                                    'onboarding.biometrics.lead',
                                                )}
                                            </p>
                                            <AuthFieldError
                                                message={
                                                    errors.name ??
                                                    missing(nameReady, 'name')
                                                }
                                                className="pt-2"
                                            />
                                        </>
                                    )}

                                    {needsReview && (
                                        <div className="mt-5">
                                            <Notice
                                                tone="info"
                                                title={t(
                                                    'onboarding.biometrics.review_title',
                                                )}
                                            >
                                                {!isCompletedReview && (
                                                    <p>
                                                        {t(
                                                            'onboarding.biometrics.review_body',
                                                        )}
                                                    </p>
                                                )}
                                                {!isImperial &&
                                                    weightLooksLikePounds && (
                                                        <button
                                                            type="button"
                                                            onClick={
                                                                treatWeightAsPounds
                                                            }
                                                            className={cn(
                                                                NOTICE_BUTTON,
                                                                'bg-foreground/[0.08] text-foreground dark:bg-white/[0.12]',
                                                            )}
                                                        >
                                                            {t(
                                                                'onboarding.biometrics.review_use_pounds',
                                                            )}
                                                        </button>
                                                    )}
                                            </Notice>
                                        </div>
                                    )}
                                </div>

                                <Dial
                                    order={1}
                                    label={t('onboarding.biometrics.age')}
                                    error={
                                        errors.age ?? missing(ageReady, 'age')
                                    }
                                    className="md:col-start-2 md:row-start-1"
                                    readout={
                                        <>
                                            <input
                                                id="age"
                                                type="text"
                                                name="age"
                                                inputMode="numeric"
                                                autoComplete="off"
                                                aria-label={t(
                                                    'onboarding.biometrics.age',
                                                )}
                                                placeholder="–"
                                                value={age}
                                                onChange={(event) => {
                                                    setAge(
                                                        typed(
                                                            event.target.value,
                                                        ),
                                                    );

                                                    if (errors.age) {
                                                        clearErrors('age');
                                                    }
                                                }}
                                                className={cn(NUMBER, 'w-20')}
                                            />
                                            <span className={UNIT}>
                                                {unit('years')}
                                            </span>
                                        </>
                                    }
                                >
                                    <MeasurementRuler
                                        {...stepLabels(
                                            t('onboarding.biometrics.age'),
                                        )}
                                        min={13}
                                        max={100}
                                        step={1}
                                        start={30}
                                        value={parseMeasurement(age)}
                                        onChange={(value) => {
                                            setAge(String(value));

                                            if (errors.age) {
                                                clearErrors('age');
                                            }
                                        }}
                                    />
                                </Dial>

                                <div
                                    style={{ animationDelay: '90ms' }}
                                    className={cn(
                                        'grid content-start gap-2.5 md:col-start-2 md:row-start-2',
                                        RISE,
                                    )}
                                >
                                    <div
                                        role="radiogroup"
                                        aria-label={t(
                                            'onboarding.biometrics.sex',
                                        )}
                                        className={cn(
                                            'relative grid h-14 grid-cols-3 rounded-full p-1.5',
                                            SURFACE,
                                            (errors.sex ||
                                                (showProblem && !sexReady)) &&
                                                'ring-2 ring-rose-500/70',
                                        )}
                                    >
                                        <span
                                            aria-hidden="true"
                                            style={{
                                                transform: `translateX(${
                                                    Math.max(
                                                        sexOptions.findIndex(
                                                            (option) =>
                                                                option.value ===
                                                                sex,
                                                        ),
                                                        0,
                                                    ) * 100
                                                }%)`,
                                            }}
                                            className={cn(
                                                SEGMENT_THUMB,
                                                'inset-y-1.5 left-1.5 w-[calc((100%-0.75rem)/3)] transition-[transform,opacity]',
                                                sex
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        {sexOptions.map((option) => (
                                            <label
                                                key={option.value}
                                                className={cn(
                                                    'relative flex items-center justify-center rounded-full text-[0.9375rem] font-semibold transition-colors duration-150 ease-out has-focus-visible:ring-2 has-focus-visible:ring-emerald-600',
                                                    sex === option.value
                                                        ? 'text-foreground'
                                                        : 'text-muted-foreground',
                                                    AUTH_PRESSABLE,
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    name="sex"
                                                    value={option.value}
                                                    checked={
                                                        sex === option.value
                                                    }
                                                    onChange={() => {
                                                        setSex(option.value);

                                                        if (errors.sex) {
                                                            clearErrors('sex');
                                                        }
                                                    }}
                                                    className="sr-only"
                                                />
                                                {option.label}
                                            </label>
                                        ))}
                                    </div>
                                    <AuthFieldError
                                        message={
                                            errors.sex ??
                                            missing(sexReady, 'sex')
                                        }
                                    />

                                    <div
                                        role="radiogroup"
                                        aria-label={t(
                                            'onboarding.biometrics.units',
                                        )}
                                        className={cn(
                                            'relative mt-2 grid h-11 grid-cols-2 rounded-full p-1',
                                            SURFACE,
                                        )}
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={cn(
                                                SEGMENT_THUMB,
                                                'inset-y-1 left-1 w-[calc(50%-0.25rem)] transition-transform',
                                                isImperial &&
                                                    'translate-x-full',
                                            )}
                                        />
                                        {(['metric', 'imperial'] as const).map(
                                            (system) => (
                                                <button
                                                    key={system}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={
                                                        measurementSystem ===
                                                        system
                                                    }
                                                    onClick={() =>
                                                        switchMeasurementSystem(
                                                            system,
                                                        )
                                                    }
                                                    className={cn(
                                                        'relative rounded-full text-sm font-semibold whitespace-nowrap transition-colors duration-150 ease-out',
                                                        measurementSystem ===
                                                            system
                                                            ? 'text-foreground'
                                                            : 'text-muted-foreground',
                                                        AUTH_PRESSABLE,
                                                    )}
                                                >
                                                    {t(
                                                        `onboarding.biometrics.units_${system}`,
                                                    )}
                                                </button>
                                            ),
                                        )}
                                    </div>
                                    <AuthFieldError
                                        message={errors.measurement_system}
                                    />
                                </div>

                                <Dial
                                    order={3}
                                    label={t(
                                        'onboarding.biometrics.height_label',
                                    )}
                                    error={
                                        heightError ??
                                        missing(heightReady, 'height')
                                    }
                                    hint={preview(
                                        'height-conversion-preview',
                                        heightPreview,
                                    )}
                                    className="md:col-start-2 md:row-start-3"
                                    readout={
                                        isImperial ? (
                                            <>
                                                <input
                                                    id="height_feet"
                                                    type="text"
                                                    name="height_feet"
                                                    inputMode="numeric"
                                                    autoComplete="off"
                                                    aria-label={t(
                                                        'onboarding.biometrics.feet_label',
                                                    )}
                                                    aria-describedby={
                                                        heightPreview
                                                            ? 'height-conversion-preview'
                                                            : undefined
                                                    }
                                                    placeholder="–"
                                                    value={
                                                        measurements.heightFeet
                                                    }
                                                    onChange={(event) =>
                                                        changeMeasurement(
                                                            'heightFeet',
                                                            typed(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                    className={cn(
                                                        NUMBER,
                                                        'w-9',
                                                    )}
                                                />
                                                <span className={UNIT}>
                                                    {unit('ft')}
                                                </span>
                                                <input
                                                    id="height_inches"
                                                    type="text"
                                                    name="height_inches"
                                                    inputMode="decimal"
                                                    autoComplete="off"
                                                    aria-label={t(
                                                        'onboarding.biometrics.inches_label',
                                                    )}
                                                    placeholder="–"
                                                    value={
                                                        measurements.heightInches
                                                    }
                                                    onChange={(event) =>
                                                        changeMeasurement(
                                                            'heightInches',
                                                            typed(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                    className={cn(
                                                        NUMBER,
                                                        'w-16',
                                                    )}
                                                />
                                                <span className={UNIT}>
                                                    {unit('in')}
                                                </span>
                                            </>
                                        ) : (
                                            <>
                                                <input
                                                    id="height"
                                                    type="text"
                                                    name="height"
                                                    inputMode="decimal"
                                                    autoComplete="off"
                                                    aria-label={t(
                                                        'onboarding.biometrics.height',
                                                    )}
                                                    aria-describedby={
                                                        heightPreview
                                                            ? 'height-conversion-preview'
                                                            : undefined
                                                    }
                                                    placeholder="–"
                                                    value={
                                                        measurements.heightCm
                                                    }
                                                    onChange={(event) =>
                                                        changeMeasurement(
                                                            'heightCm',
                                                            typed(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                    className={cn(
                                                        NUMBER,
                                                        'w-28',
                                                    )}
                                                />
                                                <span className={UNIT}>
                                                    {unit('cm')}
                                                </span>
                                            </>
                                        )
                                    }
                                >
                                    {isImperial ? (
                                        <MeasurementRuler
                                            key="inches"
                                            {...stepLabels(
                                                t(
                                                    'onboarding.biometrics.height_label',
                                                ),
                                            )}
                                            min={36}
                                            max={96}
                                            step={1}
                                            start={67}
                                            value={totalInches}
                                            onChange={changeHeightInches}
                                        />
                                    ) : (
                                        <MeasurementRuler
                                            key="centimeters"
                                            {...stepLabels(
                                                t(
                                                    'onboarding.biometrics.height_label',
                                                ),
                                            )}
                                            min={100}
                                            max={230}
                                            step={1}
                                            start={170}
                                            value={parseMeasurement(
                                                measurements.heightCm,
                                            )}
                                            onChange={(value) =>
                                                changeMeasurement(
                                                    'heightCm',
                                                    String(value),
                                                )
                                            }
                                        />
                                    )}
                                </Dial>

                                <Dial
                                    order={4}
                                    label={t(
                                        'onboarding.biometrics.weight_label',
                                    )}
                                    error={
                                        errors.weight ??
                                        missing(weightReady, 'weight')
                                    }
                                    hint={preview(
                                        'weight-conversion-preview',
                                        weightPreview,
                                    )}
                                    className="md:col-start-2 md:row-start-4"
                                    readout={
                                        <>
                                            <input
                                                id="weight"
                                                type="text"
                                                name="weight"
                                                inputMode="decimal"
                                                autoComplete="off"
                                                aria-label={t(
                                                    isImperial
                                                        ? 'onboarding.biometrics.weight_imperial'
                                                        : 'onboarding.biometrics.weight',
                                                )}
                                                aria-describedby={
                                                    weightPreview
                                                        ? 'weight-conversion-preview'
                                                        : undefined
                                                }
                                                placeholder="–"
                                                value={measurements.weight}
                                                onChange={(event) =>
                                                    changeMeasurement(
                                                        'weight',
                                                        typed(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                                className={cn(NUMBER, 'w-28')}
                                            />
                                            <span className={UNIT}>
                                                {unit(isImperial ? 'lb' : 'kg')}
                                            </span>
                                        </>
                                    }
                                >
                                    {isImperial ? (
                                        <MeasurementRuler
                                            key="pounds"
                                            {...stepLabels(
                                                t(
                                                    'onboarding.biometrics.weight_label',
                                                ),
                                            )}
                                            min={60}
                                            max={500}
                                            step={1}
                                            start={150}
                                            value={parseMeasurement(
                                                measurements.weight,
                                            )}
                                            onChange={(value) =>
                                                changeMeasurement(
                                                    'weight',
                                                    String(value),
                                                )
                                            }
                                        />
                                    ) : (
                                        <MeasurementRuler
                                            key="kilograms"
                                            {...stepLabels(
                                                t(
                                                    'onboarding.biometrics.weight_label',
                                                ),
                                            )}
                                            min={30}
                                            max={230}
                                            step={0.5}
                                            start={68}
                                            value={parseMeasurement(
                                                measurements.weight,
                                            )}
                                            onChange={(value) =>
                                                changeMeasurement(
                                                    'weight',
                                                    String(value),
                                                )
                                            }
                                        />
                                    )}
                                </Dial>

                                <div
                                    style={{ animationDelay: '225ms' }}
                                    className={cn(
                                        'flex flex-col gap-3 pt-2 md:col-start-1 md:row-span-2 md:row-start-3 md:self-end md:pt-0',
                                        RISE,
                                    )}
                                >
                                    <div>
                                        <p className="text-[0.9375rem] font-semibold text-muted-foreground">
                                            {t(
                                                'onboarding.biometrics.blood_type',
                                            )}
                                        </p>
                                        <p className="mt-0.5 text-[0.8125rem] text-muted-foreground">
                                            {t(
                                                'onboarding.biometrics.blood_type_help',
                                            )}
                                        </p>
                                        <div className="-mx-6 mt-2.5 flex [scrollbar-width:none] gap-2 overflow-x-auto overscroll-x-contain px-6 md:mx-0 md:flex-wrap md:overflow-visible md:px-0 [&::-webkit-scrollbar]:hidden">
                                            {bloodTypeOptions.map((option) => (
                                                <button
                                                    key={option.value}
                                                    type="button"
                                                    aria-pressed={
                                                        bloodType ===
                                                        option.value
                                                    }
                                                    onClick={() =>
                                                        setBloodType(
                                                            bloodType ===
                                                                option.value
                                                                ? ''
                                                                : option.value,
                                                        )
                                                    }
                                                    className={cn(
                                                        'h-11 min-w-14 shrink-0 rounded-full px-4 text-[0.9375rem] font-semibold transition-[background-color,color,scale] duration-150 ease-out active:scale-95',
                                                        bloodType ===
                                                            option.value
                                                            ? 'bg-foreground text-background'
                                                            : 'bg-foreground/[0.055] text-foreground dark:bg-white/[0.08]',
                                                        AUTH_PRESSABLE,
                                                    )}
                                                >
                                                    {option.label}
                                                </button>
                                            ))}
                                        </div>
                                        <AuthFieldError
                                            message={errors.blood_type}
                                            className="pt-2"
                                        />
                                    </div>

                                    <div className="mt-auto grid gap-3 pt-4">
                                        {errors.weight_in_pounds && (
                                            <Notice
                                                tone="check"
                                                title={t(
                                                    'onboarding.biometrics.unit_check.pounds_title',
                                                    {
                                                        weight: measurements.weight,
                                                    },
                                                )}
                                            >
                                                <p>
                                                    {t(
                                                        'onboarding.biometrics.unit_check.pounds_body',
                                                        {
                                                            weight: measurements.weight,
                                                            height: measurements.heightCm,
                                                        },
                                                    )}
                                                </p>
                                                <div className="grid gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={
                                                            treatWeightAsPounds
                                                        }
                                                        className={cn(
                                                            NOTICE_BUTTON,
                                                            'bg-foreground text-background',
                                                        )}
                                                    >
                                                        {t(
                                                            'onboarding.biometrics.unit_check.use_pounds',
                                                            {
                                                                weight: measurements.weight,
                                                            },
                                                        )}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={
                                                            confirmMeasurements
                                                        }
                                                        className={cn(
                                                            NOTICE_BUTTON,
                                                            'bg-foreground/[0.08] text-foreground dark:bg-white/[0.12]',
                                                        )}
                                                    >
                                                        {t(
                                                            'onboarding.biometrics.unit_check.keep_kilograms',
                                                            {
                                                                weight: measurements.weight,
                                                            },
                                                        )}
                                                    </button>
                                                </div>
                                            </Notice>
                                        )}

                                        {errors.measurements && (
                                            <Notice
                                                tone="check"
                                                title={t(
                                                    'onboarding.biometrics.unit_check.measurements_title',
                                                )}
                                            >
                                                <p>
                                                    {t(
                                                        'onboarding.biometrics.unit_check.measurements_body',
                                                        {
                                                            weight: weightLabel,
                                                            height: heightLabel,
                                                        },
                                                    )}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={
                                                        confirmMeasurements
                                                    }
                                                    className={cn(
                                                        NOTICE_BUTTON,
                                                        'bg-foreground/[0.08] text-foreground dark:bg-white/[0.12]',
                                                    )}
                                                >
                                                    {t(
                                                        'onboarding.biometrics.unit_check.measurements_confirm',
                                                    )}
                                                </button>
                                            </Notice>
                                        )}

                                        <AuthSubmitButton
                                            processing={processing}
                                            ready={ready}
                                        >
                                            {t(
                                                isCompletedReview
                                                    ? 'onboarding.biometrics.review_save'
                                                    : 'onboarding.biometrics.continue',
                                            )}
                                        </AuthSubmitButton>
                                    </div>
                                </div>
                            </div>
                        </>
                    );
                }}
            </Form>
        </AuthScreen>
    );
}
