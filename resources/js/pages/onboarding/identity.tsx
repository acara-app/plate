import {
    AUTH_HEADLINE,
    AUTH_LEAD,
    AUTH_PRESSABLE,
    AuthCheck,
    AuthFieldError,
    AuthScreen,
    AuthSubmitButton,
} from '@/components/auth/auth-screen';
import {
    ONBOARDING_COLUMNS,
    ONBOARDING_LABEL,
    ONBOARDING_RISE,
    ONBOARDING_SURFACE,
    OnboardingHeader,
} from '@/components/onboarding/onboarding-shell';
import { cn } from '@/lib/utils';
import onboarding from '@/routes/onboarding';
import {
    AnimalProductChoice,
    GoalChoice,
    IntensityChoice,
    Profile,
} from '@/types/user';
import { Form } from '@inertiajs/react';
import {
    Activity,
    Beef,
    Dumbbell,
    Fish,
    Flame,
    HeartPulse,
    type LucideIcon,
    Rabbit,
    Salad,
    Sprout,
    Turtle,
} from 'lucide-react';
import { type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Choice {
    value: string;
    label: string;
    description?: string;
    Icon: LucideIcon;
}

const OPTIONS = 'onboarding.identity.options';

const GOALS: Choice[] = [
    {
        value: GoalChoice.Spikes,
        label: `${OPTIONS}.goals.spikes`,
        description: `${OPTIONS}.goals.spikes_desc`,
        Icon: Activity,
    },
    {
        value: GoalChoice.WeightLoss,
        label: `${OPTIONS}.goals.weight_loss`,
        description: `${OPTIONS}.goals.weight_loss_desc`,
        Icon: Flame,
    },
    {
        value: GoalChoice.HeartHealth,
        label: `${OPTIONS}.goals.heart_health`,
        description: `${OPTIONS}.goals.heart_health_desc`,
        Icon: HeartPulse,
    },
    {
        value: GoalChoice.BuildMuscle,
        label: `${OPTIONS}.goals.build_muscle`,
        description: `${OPTIONS}.goals.build_muscle_desc`,
        Icon: Dumbbell,
    },
    {
        value: GoalChoice.HealthyEating,
        label: `${OPTIONS}.goals.healthy_eating`,
        description: `${OPTIONS}.goals.healthy_eating_desc`,
        Icon: Salad,
    },
];

const ANIMAL_PRODUCTS: Choice[] = [
    {
        value: AnimalProductChoice.Omnivore,
        label: `${OPTIONS}.animal_products.omnivore`,
        Icon: Beef,
    },
    {
        value: AnimalProductChoice.Pescatarian,
        label: `${OPTIONS}.animal_products.pescatarian`,
        Icon: Fish,
    },
    {
        value: AnimalProductChoice.Vegan,
        label: `${OPTIONS}.animal_products.vegan`,
        Icon: Sprout,
    },
];

const INTENSITIES: Choice[] = [
    {
        value: IntensityChoice.Balanced,
        label: `${OPTIONS}.intensity.balanced`,
        Icon: Turtle,
    },
    {
        value: IntensityChoice.Aggressive,
        label: `${OPTIONS}.intensity.aggressive`,
        Icon: Rabbit,
    },
];

function Option({
    name,
    choice,
    checked,
    onChange,
    compact = false,
}: {
    name: string;
    choice: Choice;
    checked: boolean;
    onChange: () => void;
    compact?: boolean;
}) {
    const { t } = useTranslation('common');

    return (
        <label
            className={cn(
                'flex items-center gap-3.5 rounded-[1.25rem] p-3 transition-[background-color,scale] duration-150 ease-out active:scale-[0.985] has-checked:bg-emerald-500/[0.14] dark:has-checked:bg-emerald-400/[0.16]',
                compact && 'flex-col items-start gap-3 p-4',
                ONBOARDING_SURFACE,
                AUTH_PRESSABLE,
            )}
        >
            <span
                className={cn(
                    'flex size-11 shrink-0 items-center justify-center rounded-2xl transition-colors duration-150 ease-out',
                    checked
                        ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                        : 'bg-background text-foreground dark:bg-white/10',
                )}
            >
                <choice.Icon
                    className="size-5"
                    strokeWidth={2.25}
                    aria-hidden="true"
                />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-[1.0625rem] leading-snug font-semibold text-foreground">
                    {t(choice.label)}
                </span>
                {choice.description && (
                    <span className="block text-sm text-muted-foreground">
                        {t(choice.description)}
                    </span>
                )}
            </span>
            <input
                type="radio"
                name={name}
                value={choice.value}
                checked={checked}
                onChange={onChange}
                className="peer sr-only"
            />
            {!compact && <AuthCheck checked={checked} className="mr-1" />}
        </label>
    );
}

function Question({
    order,
    label,
    error,
    children,
}: {
    order: number;
    label: string;
    error?: string | false;
    children: ReactNode;
}) {
    return (
        <section
            style={{ animationDelay: `${order * 45}ms` }}
            className={ONBOARDING_RISE}
        >
            <h2 className={ONBOARDING_LABEL}>{label}</h2>
            {children}
            <AuthFieldError message={error} className="pt-2" />
        </section>
    );
}

interface Props {
    profile?: Profile;
}

export default function Identity({ profile }: Props) {
    const { t } = useTranslation('common');
    const [goal, setGoal] = useState<string>(profile?.goal_choice ?? '');
    const [animalProduct, setAnimalProduct] = useState<string>(
        profile?.animal_product_choice ?? '',
    );
    const [intensity, setIntensity] = useState<string>(
        profile?.intensity_choice ?? '',
    );
    const [showProblem, setShowProblem] = useState(false);
    const ready = goal !== '' && animalProduct !== '' && intensity !== '';
    const missing = (value: string, key: string) =>
        showProblem && value === '' && t(`onboarding.identity.missing.${key}`);

    return (
        <AuthScreen
            title={t('onboarding.identity.title')}
            className="md:min-h-0 md:max-w-[54rem]"
        >
            <OnboardingHeader
                step={2}
                backHref={onboarding.biometrics.show.url()}
            />
            <Form
                {...onboarding.identity.store.form()}
                noValidate
                onBefore={() => {
                    if (!ready) {
                        setShowProblem(true);

                        return false;
                    }
                }}
                disableWhileProcessing
                className={ONBOARDING_COLUMNS}
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-7">
                            <div className={ONBOARDING_RISE}>
                                <h1 className={AUTH_HEADLINE}>
                                    {t('onboarding.identity.heading')}
                                </h1>
                                <p className={AUTH_LEAD}>
                                    {t('onboarding.identity.description')}
                                </p>
                            </div>
                            <Question
                                order={1}
                                label={t(
                                    'onboarding.identity.questions.mission',
                                )}
                                error={
                                    errors.goal_choice ??
                                    missing(goal, 'goal_choice')
                                }
                            >
                                <div role="radiogroup" className="grid gap-2">
                                    {GOALS.map((choice) => (
                                        <Option
                                            key={choice.value}
                                            name="goal_choice"
                                            choice={choice}
                                            checked={goal === choice.value}
                                            onChange={() =>
                                                setGoal(choice.value)
                                            }
                                        />
                                    ))}
                                </div>
                            </Question>
                        </div>

                        <div className="grid gap-7 md:pt-2">
                            <Question
                                order={2}
                                label={t(
                                    'onboarding.identity.questions.animal_products',
                                )}
                                error={
                                    errors.animal_product_choice ??
                                    missing(
                                        animalProduct,
                                        'animal_product_choice',
                                    )
                                }
                            >
                                <div role="radiogroup" className="grid gap-2">
                                    {ANIMAL_PRODUCTS.map((choice) => (
                                        <Option
                                            key={choice.value}
                                            name="animal_product_choice"
                                            choice={choice}
                                            checked={
                                                animalProduct === choice.value
                                            }
                                            onChange={() =>
                                                setAnimalProduct(choice.value)
                                            }
                                        />
                                    ))}
                                </div>
                            </Question>

                            <Question
                                order={3}
                                label={t(
                                    'onboarding.identity.questions.intensity',
                                )}
                                error={
                                    errors.intensity_choice ??
                                    missing(intensity, 'intensity_choice')
                                }
                            >
                                <div
                                    role="radiogroup"
                                    className="grid grid-cols-2 gap-2"
                                >
                                    {INTENSITIES.map((choice) => (
                                        <Option
                                            key={choice.value}
                                            compact
                                            name="intensity_choice"
                                            choice={choice}
                                            checked={intensity === choice.value}
                                            onChange={() =>
                                                setIntensity(choice.value)
                                            }
                                        />
                                    ))}
                                </div>
                            </Question>

                            <AuthSubmitButton
                                processing={processing}
                                ready={ready}
                            >
                                {t('onboarding.identity.submit')}
                            </AuthSubmitButton>
                        </div>
                    </>
                )}
            </Form>
        </AuthScreen>
    );
}
