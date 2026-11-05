import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Form, Head, Link } from '@inertiajs/react';
import { Info, LoaderCircle, Scale } from 'lucide-react';

import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import useSharedProps from '@/hooks/use-shared-props';
import {
    centimetersToFeetAndInches,
    defaultMeasurementSystem,
    exactFeetAndInches,
    exactPounds,
    feetAndInchesToCentimeters,
    formatMeasurement,
    isMeasurementSystem,
    kilogramsToPounds,
    type MeasurementSystem,
    parseMeasurement,
    poundsToKilograms,
} from '@/lib/body-measurements';
import { BloodTypeOption, Profile, SexOption } from '@/types/user';
import { useRef, useState } from 'react';
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

function exactImperialSubmission(
    measurements: Measurements,
): Record<string, string> {
    const height =
        measurements.exactHeightCm === null
            ? null
            : exactFeetAndInches(measurements.exactHeightCm);

    return {
        ...(measurements.exactWeightKg === null
            ? {}
            : { weight: exactPounds(measurements.exactWeightKg) }),
        ...(height === null
            ? {}
            : { height_feet: height.feet, height_inches: height.inches }),
    };
}

const MEASUREMENT_PROMPTS = ['weight_in_pounds', 'measurements'];

const MEASUREMENT_ERRORS = [
    ...MEASUREMENT_PROMPTS,
    'weight',
    'height',
    'height_feet',
    'height_inches',
];

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
    const { locale } = useSharedProps();
    const needsReview = profile?.needs_re_onboarding === true;
    const isCompletedReview =
        needsReview && profile.onboarding_completed === true;
    const [age, setAge] = useState<string>(currentAge?.toString() ?? '');
    const [bloodType, setBloodType] = useState<string>(
        profile?.blood_type ?? '',
    );
    const [measurementSystem, setMeasurementSystem] =
        useState<MeasurementSystem>(() =>
            needsReview ? 'metric' : defaultMeasurementSystem(locale),
        );
    const [measurements, setMeasurements] = useState<Measurements>(() =>
        initialMeasurements(profile, measurementSystem),
    );
    const confirmMeasurementsRef = useRef(false);
    const isImperial = measurementSystem === 'imperial';

    const weightLabel = `${measurements.weight} ${t(isImperial ? 'onboarding.biometrics.unit_lb' : 'onboarding.biometrics.unit_kg')}`;
    const heightLabel = isImperial
        ? `${measurements.heightFeet || 0} ${t('onboarding.biometrics.unit_ft')} ${measurements.heightInches || 0} ${t('onboarding.biometrics.unit_in')}`
        : `${measurements.heightCm} ${t('onboarding.biometrics.unit_cm')}`;
    const weightPreview =
        isImperial && measurements.exactWeightKg !== null
            ? `${formatMeasurement(measurements.exactWeightKg, 2)} ${t('onboarding.biometrics.unit_kg')}`
            : isValidMeasurement(
                    measurements.weight,
                    isImperial ? 45 : 20,
                    isImperial ? 1100 : 500,
                )
              ? `${isImperial ? poundsToKilograms(measurements.weight) : kilogramsToPounds(measurements.weight)} ${t(isImperial ? 'onboarding.biometrics.unit_kg' : 'onboarding.biometrics.unit_lb')}`
              : null;
    const metricHeightPreview = centimetersToFeetAndInches(
        measurements.heightCm,
    );
    const hasValidImperialHeight =
        isValidMeasurement(measurements.heightFeet, 2, 8) &&
        Number.isInteger(Number(measurements.heightFeet)) &&
        (measurements.heightInches.trim() === '' ||
            isValidMeasurement(measurements.heightInches, 0, 11.9));
    const heightPreview = isImperial
        ? measurements.exactHeightCm !== null
            ? `${formatMeasurement(measurements.exactHeightCm, 2)} ${t('onboarding.biometrics.unit_cm')}`
            : hasValidImperialHeight
              ? `${feetAndInchesToCentimeters(measurements.heightFeet, measurements.heightInches)} ${t('onboarding.biometrics.unit_cm')}`
              : null
        : isValidMeasurement(measurements.heightCm, 50, 300)
          ? `${metricHeightPreview.feet} ${t('onboarding.biometrics.unit_ft')} ${metricHeightPreview.inches} ${t('onboarding.biometrics.unit_in')}`
          : null;
    const heightConversionPreview = heightPreview && (
        <p
            id="height-conversion-preview"
            className="text-sm text-muted-foreground"
        >
            {t('onboarding.biometrics.conversion_preview', {
                measurement: heightPreview,
            })}
        </p>
    );

    return (
        <>
            <Head
                title={t(
                    isCompletedReview
                        ? 'onboarding.biometrics.review_heading'
                        : 'onboarding.biometrics.title',
                )}
            />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-md">
                    {/* Progress Bar */}
                    {!isCompletedReview && (
                        <div className="mb-8">
                            <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                                <span>
                                    {t('onboarding.biometrics.step', {
                                        current: 1,
                                        total: 3,
                                    })}
                                </span>
                                <span>33%</span>
                            </div>
                            <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div className="relative h-2 w-[33%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                    <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                    <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="rounded-lg bg-white px-5 py-6 shadow-lg sm:p-8 dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t(
                                isCompletedReview
                                    ? 'onboarding.biometrics.review_heading'
                                    : 'onboarding.biometrics.heading',
                            )}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t(
                                isCompletedReview
                                    ? 'onboarding.biometrics.review_body'
                                    : 'onboarding.biometrics.description',
                            )}
                        </p>

                        <Form
                            {...onboarding.biometrics.store.form()}
                            transform={(data) => {
                                const submitted = isImperial
                                    ? {
                                          ...data,
                                          ...exactImperialSubmission(
                                              measurements,
                                          ),
                                      }
                                    : data;

                                return confirmMeasurementsRef.current
                                    ? {
                                          ...submitted,
                                          confirm_measurements: true,
                                      }
                                    : submitted;
                            }}
                            onFinish={() => {
                                confirmMeasurementsRef.current = false;
                            }}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors, clearErrors, submit }) => {
                                const clearMeasurementPrompts = () => {
                                    if (
                                        errors.weight_in_pounds ||
                                        errors.measurements
                                    ) {
                                        clearErrors(...MEASUREMENT_PROMPTS);
                                    }
                                };

                                const clearMeasurementErrors = () => {
                                    const present = MEASUREMENT_ERRORS.filter(
                                        (key) =>
                                            errors[key as keyof typeof errors],
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
                                    clearMeasurementPrompts();
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
                                                ...heightInFeetAndInches(
                                                    heightCm,
                                                ),
                                                exactWeightKg: weightKg,
                                                exactHeightCm: heightCm,
                                            };
                                        }

                                        return {
                                            ...current,
                                            weight:
                                                current.exactWeightKg === null
                                                    ? poundsToKilograms(
                                                          current.weight,
                                                      )
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
                                        const heightCm = parseMeasurement(
                                            current.heightCm,
                                        );

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

                                return (
                                    <>
                                        {needsReview && (
                                            <Alert
                                                role="status"
                                                className="min-w-0 border-primary/30 bg-primary/5 has-[>svg]:grid-cols-[1rem_minmax(0,1fr)]"
                                            >
                                                <Info className="text-primary" />
                                                <AlertTitle className="line-clamp-none min-w-0 wrap-break-word">
                                                    {t(
                                                        'onboarding.biometrics.review_title',
                                                    )}
                                                </AlertTitle>
                                                <AlertDescription className="min-w-0 wrap-break-word">
                                                    {!isCompletedReview && (
                                                        <p>
                                                            {t(
                                                                'onboarding.biometrics.review_body',
                                                            )}
                                                        </p>
                                                    )}
                                                    {!isImperial &&
                                                        weightLooksLikePounds && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                className="mt-2 h-auto min-h-11 w-full min-w-0 py-2 whitespace-normal"
                                                                onClick={
                                                                    treatWeightAsPounds
                                                                }
                                                            >
                                                                {t(
                                                                    'onboarding.biometrics.review_use_pounds',
                                                                )}
                                                            </Button>
                                                        )}
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {errors.weight_in_pounds && (
                                            <Alert className="min-w-0 border-amber-300 bg-amber-50 has-[>svg]:grid-cols-[1rem_minmax(0,1fr)] dark:border-amber-900/50 dark:bg-amber-950/40">
                                                <Scale className="text-amber-700 dark:text-amber-400" />
                                                <AlertTitle className="line-clamp-none min-w-0 wrap-break-word">
                                                    {t(
                                                        'onboarding.biometrics.unit_check.pounds_title',
                                                        {
                                                            weight: measurements.weight,
                                                        },
                                                    )}
                                                </AlertTitle>
                                                <AlertDescription className="min-w-0 wrap-break-word">
                                                    <p>
                                                        {t(
                                                            'onboarding.biometrics.unit_check.pounds_body',
                                                            {
                                                                weight: measurements.weight,
                                                                height: measurements.heightCm,
                                                            },
                                                        )}
                                                    </p>
                                                    <div className="mt-2 flex w-full min-w-0 flex-col gap-2">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            className="h-auto min-h-11 w-full min-w-0 py-2 whitespace-normal"
                                                            onClick={
                                                                treatWeightAsPounds
                                                            }
                                                        >
                                                            {t(
                                                                'onboarding.biometrics.unit_check.use_pounds',
                                                                {
                                                                    weight: measurements.weight,
                                                                },
                                                            )}
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            className="h-auto min-h-11 w-full min-w-0 py-2 whitespace-normal"
                                                            onClick={
                                                                confirmMeasurements
                                                            }
                                                        >
                                                            {t(
                                                                'onboarding.biometrics.unit_check.keep_kilograms',
                                                                {
                                                                    weight: measurements.weight,
                                                                },
                                                            )}
                                                        </Button>
                                                    </div>
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {errors.measurements && (
                                            <Alert className="min-w-0 border-amber-300 bg-amber-50 has-[>svg]:grid-cols-[1rem_minmax(0,1fr)] dark:border-amber-900/50 dark:bg-amber-950/40">
                                                <Scale className="text-amber-700 dark:text-amber-400" />
                                                <AlertTitle className="line-clamp-none min-w-0 wrap-break-word">
                                                    {t(
                                                        'onboarding.biometrics.unit_check.measurements_title',
                                                    )}
                                                </AlertTitle>
                                                <AlertDescription className="min-w-0 wrap-break-word">
                                                    <p>
                                                        {t(
                                                            'onboarding.biometrics.unit_check.measurements_body',
                                                            {
                                                                weight: weightLabel,
                                                                height: heightLabel,
                                                            },
                                                        )}
                                                    </p>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        className="mt-2 h-auto min-h-11 w-full min-w-0 py-2 whitespace-normal"
                                                        onClick={
                                                            confirmMeasurements
                                                        }
                                                    >
                                                        {t(
                                                            'onboarding.biometrics.unit_check.measurements_confirm',
                                                        )}
                                                    </Button>
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {/* Age */}
                                        <div className="grid gap-2">
                                            <Label htmlFor="age">
                                                {t('onboarding.biometrics.age')}
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    id="age"
                                                    type="number"
                                                    name="age"
                                                    inputMode="numeric"
                                                    value={age}
                                                    onChange={(e) =>
                                                        setAge(e.target.value)
                                                    }
                                                    step="1"
                                                    min="13"
                                                    max="120"
                                                    required
                                                    aria-describedby="age_help"
                                                    placeholder={t(
                                                        'onboarding.biometrics.age_placeholder',
                                                    )}
                                                    className="pr-16"
                                                />
                                                <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">
                                                    {t(
                                                        'onboarding.biometrics.unit_years',
                                                    )}
                                                </span>
                                            </div>
                                            <InputError message={errors.age} />
                                            <p
                                                id="age_help"
                                                className="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {t(
                                                    'onboarding.biometrics.age_help',
                                                )}
                                            </p>
                                        </div>

                                        <div className="grid gap-2">
                                            <Label id="measurement_system_label">
                                                {t(
                                                    'onboarding.biometrics.units',
                                                )}
                                            </Label>
                                            <input
                                                type="hidden"
                                                name="measurement_system"
                                                value={measurementSystem}
                                            />
                                            <ToggleGroup
                                                type="single"
                                                variant="outline"
                                                joined
                                                value={measurementSystem}
                                                onValueChange={(value) => {
                                                    if (
                                                        isMeasurementSystem(
                                                            value,
                                                        )
                                                    ) {
                                                        switchMeasurementSystem(
                                                            value,
                                                        );
                                                    }
                                                }}
                                                aria-labelledby="measurement_system_label"
                                                className="w-full"
                                            >
                                                <ToggleGroupItem
                                                    value="metric"
                                                    className="h-auto min-h-11 flex-1 py-2 whitespace-normal"
                                                >
                                                    {t(
                                                        'onboarding.biometrics.units_metric',
                                                    )}
                                                </ToggleGroupItem>
                                                <ToggleGroupItem
                                                    value="imperial"
                                                    className="h-auto min-h-11 flex-1 py-2 whitespace-normal"
                                                >
                                                    {t(
                                                        'onboarding.biometrics.units_imperial',
                                                    )}
                                                </ToggleGroupItem>
                                            </ToggleGroup>
                                            <InputError
                                                message={
                                                    errors.measurement_system
                                                }
                                            />
                                        </div>

                                        {/* Height */}
                                        {isImperial ? (
                                            <div className="grid gap-2">
                                                <Label htmlFor="height_feet">
                                                    {t(
                                                        'onboarding.biometrics.height_imperial',
                                                    )}
                                                </Label>
                                                <div className="grid grid-cols-2 gap-3">
                                                    <div className="relative">
                                                        <Input
                                                            id="height_feet"
                                                            type="number"
                                                            name="height_feet"
                                                            aria-describedby={
                                                                heightPreview
                                                                    ? 'height-conversion-preview'
                                                                    : undefined
                                                            }
                                                            value={
                                                                measurements.heightFeet
                                                            }
                                                            onChange={(e) =>
                                                                changeMeasurement(
                                                                    'heightFeet',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            step="1"
                                                            min="2"
                                                            max="8"
                                                            required
                                                            aria-label={t(
                                                                'onboarding.biometrics.feet_label',
                                                            )}
                                                            placeholder={t(
                                                                'onboarding.biometrics.feet_label',
                                                            )}
                                                            className="pr-10"
                                                        />
                                                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">
                                                            {t(
                                                                'onboarding.biometrics.unit_ft',
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="relative">
                                                        <Input
                                                            id="height_inches"
                                                            type="number"
                                                            name="height_inches"
                                                            aria-describedby={
                                                                heightPreview
                                                                    ? 'height-conversion-preview'
                                                                    : undefined
                                                            }
                                                            value={
                                                                measurements.heightInches
                                                            }
                                                            onChange={(e) =>
                                                                changeMeasurement(
                                                                    'heightInches',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            step="0.1"
                                                            min="0"
                                                            max="11.9"
                                                            aria-label={t(
                                                                'onboarding.biometrics.inches_label',
                                                            )}
                                                            placeholder={t(
                                                                'onboarding.biometrics.inches_label',
                                                            )}
                                                            className="pr-10"
                                                        />
                                                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">
                                                            {t(
                                                                'onboarding.biometrics.unit_in',
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                                {heightConversionPreview}
                                                <InputError
                                                    message={errors.height_feet}
                                                />
                                                <InputError
                                                    message={
                                                        errors.height_inches
                                                    }
                                                />
                                            </div>
                                        ) : (
                                            <div className="grid gap-2">
                                                <Label htmlFor="height">
                                                    {t(
                                                        'onboarding.biometrics.height',
                                                    )}
                                                </Label>
                                                <Input
                                                    id="height"
                                                    type="number"
                                                    name="height"
                                                    aria-describedby={
                                                        heightPreview
                                                            ? 'height-conversion-preview'
                                                            : undefined
                                                    }
                                                    value={
                                                        measurements.heightCm
                                                    }
                                                    onChange={(e) =>
                                                        changeMeasurement(
                                                            'heightCm',
                                                            e.target.value,
                                                        )
                                                    }
                                                    step="0.01"
                                                    min="50"
                                                    max="300"
                                                    required
                                                    placeholder={t(
                                                        'onboarding.biometrics.height_placeholder',
                                                    )}
                                                />
                                                {heightConversionPreview}
                                                <InputError
                                                    message={errors.height}
                                                />
                                            </div>
                                        )}

                                        {/* Weight */}
                                        <div className="grid gap-2">
                                            <Label htmlFor="weight">
                                                {t(
                                                    isImperial
                                                        ? 'onboarding.biometrics.weight_imperial'
                                                        : 'onboarding.biometrics.weight',
                                                )}
                                            </Label>
                                            <Input
                                                id="weight"
                                                type="number"
                                                name="weight"
                                                aria-describedby={
                                                    weightPreview
                                                        ? 'weight-conversion-preview'
                                                        : undefined
                                                }
                                                value={measurements.weight}
                                                onChange={(e) =>
                                                    changeMeasurement(
                                                        'weight',
                                                        e.target.value,
                                                    )
                                                }
                                                step="0.01"
                                                min={isImperial ? '45' : '20'}
                                                max={
                                                    isImperial ? '1100' : '500'
                                                }
                                                required
                                                placeholder={t(
                                                    isImperial
                                                        ? 'onboarding.biometrics.weight_imperial_placeholder'
                                                        : 'onboarding.biometrics.weight_placeholder',
                                                )}
                                            />
                                            {weightPreview && (
                                                <p
                                                    id="weight-conversion-preview"
                                                    className="text-sm text-muted-foreground"
                                                >
                                                    {t(
                                                        'onboarding.biometrics.conversion_preview',
                                                        {
                                                            measurement:
                                                                weightPreview,
                                                        },
                                                    )}
                                                </p>
                                            )}
                                            <InputError
                                                message={errors.weight}
                                            />
                                        </div>

                                        {/* Sex */}
                                        <div className="grid gap-2">
                                            <Label htmlFor="sex">
                                                {t('onboarding.biometrics.sex')}
                                            </Label>
                                            <Select
                                                name="sex"
                                                defaultValue={
                                                    profile?.sex || ''
                                                }
                                                required
                                            >
                                                <SelectTrigger id="sex">
                                                    <SelectValue
                                                        placeholder={t(
                                                            'onboarding.biometrics.sex_placeholder',
                                                        )}
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {sexOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.sex} />
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {t(
                                                    'onboarding.biometrics.sex_help',
                                                )}
                                            </p>
                                        </div>

                                        {/* Blood Type */}
                                        <div className="grid gap-2">
                                            <div className="flex items-center justify-between">
                                                <Label htmlFor="blood_type">
                                                    {t(
                                                        'onboarding.biometrics.blood_type',
                                                    )}
                                                </Label>
                                                {bloodType && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setBloodType('')
                                                        }
                                                        className="text-xs text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                                    >
                                                        {t(
                                                            'onboarding.biometrics.blood_type_clear',
                                                        )}
                                                    </button>
                                                )}
                                            </div>
                                            <Select
                                                name="blood_type"
                                                value={bloodType}
                                                onValueChange={setBloodType}
                                            >
                                                <SelectTrigger id="blood_type">
                                                    <SelectValue
                                                        placeholder={t(
                                                            'onboarding.biometrics.blood_type_placeholder',
                                                        )}
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {bloodTypeOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.blood_type}
                                            />
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {t(
                                                    'onboarding.biometrics.blood_type_help',
                                                )}
                                            </p>
                                        </div>

                                        {/* Footer Section */}
                                        <div className="flex flex-col items-center gap-4">
                                            {/* Action Buttons */}
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="h-auto min-h-11 max-w-full min-w-30 py-2 whitespace-normal"
                                            >
                                                {processing && (
                                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                                )}
                                                {t(
                                                    isCompletedReview
                                                        ? 'onboarding.biometrics.review_save'
                                                        : 'onboarding.biometrics.continue',
                                                )}
                                            </Button>

                                            {/* Exit Link - Centered Below */}
                                            <Link
                                                href={dashboard.url()}
                                                className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                            >
                                                {t(
                                                    'onboarding.biometrics.exit',
                                                )}
                                            </Link>
                                        </div>
                                    </>
                                );
                            }}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
