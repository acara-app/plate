import {
    AUTH_HEADLINE,
    AUTH_LEAD,
    AUTH_PRESSABLE,
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
import { Form } from '@inertiajs/react';
import { Check, NotebookPen, Plus } from 'lucide-react';
import { type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface CategoryOption {
    value: string;
    label: string;
}

interface SeverityOption {
    value: string;
    label: string;
}

interface ExistingAttribute {
    id: number;
    category: string;
    value: string;
    severity: string | null;
    notes: string | null;
}

interface AttributeEntry {
    category: string;
    value: string;
    severity: string;
    notes: string;
}

interface Props {
    existingAttributes: ExistingAttribute[];
    categories: CategoryOption[];
    severityOptions: SeverityOption[];
}

const ALLERGY = 'allergy';

const CATEGORY_DETAILS: Record<
    string,
    { label: string; suggestions: string[] }
> = {
    allergy: {
        label: 'onboarding.dietary_preferences.allergies',
        suggestions: [
            'Peanuts',
            'Tree Nuts',
            'Milk',
            'Eggs',
            'Wheat',
            'Soy',
            'Fish',
            'Shellfish',
            'Sesame',
            'Gluten',
        ],
    },
    intolerance: {
        label: 'onboarding.dietary_preferences.intolerances',
        suggestions: ['Lactose', 'Gluten', 'Fructose', 'Caffeine', 'Histamine'],
    },
    dietary_pattern: {
        label: 'onboarding.dietary_preferences.patterns',
        suggestions: [
            'Low carb',
            'High protein',
            'Low sodium',
            'Gluten-free',
            'Dairy-free',
            'Intermittent fasting',
        ],
    },
    dislike: {
        label: 'onboarding.dietary_preferences.dislikes',
        suggestions: [
            'Mushrooms',
            'Cilantro',
            'Olives',
            'Seafood',
            'Spicy food',
            'Eggplant',
        ],
    },
    restriction: {
        label: 'onboarding.dietary_preferences.restrictions',
        suggestions: ['Halal', 'Kosher', 'No pork', 'No beef', 'No alcohol'],
    },
};

function isSame(a: string, b: string): boolean {
    return a.trim().toLowerCase() === b.trim().toLowerCase();
}

function Chip({
    selected,
    onClick,
    children,
}: {
    selected: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={selected}
            onClick={onClick}
            className={cn(
                'flex h-11 shrink-0 items-center gap-1.5 rounded-full px-4 text-[0.9375rem] font-semibold transition-[background-color,color,scale] duration-150 ease-out active:scale-95',
                selected
                    ? 'bg-foreground text-background'
                    : 'bg-foreground/[0.055] text-foreground dark:bg-white/[0.08]',
                AUTH_PRESSABLE,
            )}
        >
            {selected && (
                <Check
                    className="-ml-1 size-4 duration-150 ease-out motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-75"
                    strokeWidth={3}
                    aria-hidden="true"
                />
            )}
            {children}
        </button>
    );
}

function CategoryChips({
    suggestions,
    selected,
    onToggle,
    onAdd,
}: {
    suggestions: string[];
    selected: AttributeEntry[];
    onToggle: (value: string) => void;
    onAdd: (value: string) => void;
}) {
    const { t } = useTranslation('common');
    const [isAdding, setIsAdding] = useState(false);
    const [draft, setDraft] = useState('');
    const custom = selected.filter(
        (entry) =>
            !suggestions.some((suggestion) => isSame(suggestion, entry.value)),
    );

    const commit = () => {
        onAdd(draft);
        setDraft('');
        setIsAdding(false);
    };

    return (
        <div className="flex flex-wrap gap-2">
            {suggestions.map((suggestion) => (
                <Chip
                    key={suggestion}
                    selected={selected.some((entry) =>
                        isSame(entry.value, suggestion),
                    )}
                    onClick={() => onToggle(suggestion)}
                >
                    {suggestion}
                </Chip>
            ))}
            {custom.map((entry) => (
                <Chip
                    key={entry.value}
                    selected
                    onClick={() => onToggle(entry.value)}
                >
                    {entry.value}
                </Chip>
            ))}
            {isAdding ? (
                <input
                    type="text"
                    autoFocus
                    enterKeyHint="done"
                    maxLength={255}
                    aria-label={t('onboarding.dietary_preferences.other_label')}
                    placeholder={t(
                        'onboarding.dietary_preferences.other_placeholder',
                    )}
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                    onBlur={commit}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            commit();
                        }

                        if (event.key === 'Escape') {
                            setDraft('');
                            setIsAdding(false);
                        }
                    }}
                    className="h-11 w-52 rounded-full bg-foreground/[0.055] px-4 text-base font-medium text-foreground caret-emerald-600 outline-none placeholder:font-normal placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-emerald-600 dark:bg-white/[0.08]"
                />
            ) : (
                <button
                    type="button"
                    onClick={() => setIsAdding(true)}
                    className={cn(
                        'flex h-11 items-center gap-1 rounded-full px-3.5 text-[0.9375rem] font-medium text-muted-foreground inset-ring-[1.5px] inset-ring-foreground/15 transition-[scale] duration-150 ease-out active:scale-95',
                        AUTH_PRESSABLE,
                    )}
                >
                    <Plus className="size-4" aria-hidden="true" />
                    {t('onboarding.dietary_preferences.other')}
                </button>
            )}
        </div>
    );
}

function DetailRow({
    entry,
    severityOptions,
    onChange,
}: {
    entry: AttributeEntry;
    severityOptions: SeverityOption[];
    onChange: (field: 'severity' | 'notes', value: string) => void;
}) {
    const { t } = useTranslation('common');
    const [showNote, setShowNote] = useState(entry.notes !== '');

    return (
        <div
            className={cn(
                'grid gap-2.5 rounded-3xl p-3.5 duration-200 ease-out motion-safe:animate-in motion-safe:fade-in',
                ONBOARDING_SURFACE,
            )}
        >
            <div className="flex items-center gap-2">
                <span className="min-w-0 flex-1 truncate pl-1.5 text-[0.9375rem] font-bold text-foreground">
                    {entry.value}
                </span>
                <button
                    type="button"
                    aria-pressed={showNote}
                    aria-label={t('onboarding.dietary_preferences.notes')}
                    onClick={() => setShowNote((value) => !value)}
                    className={cn(
                        'flex size-9 items-center justify-center rounded-full text-muted-foreground transition-[scale,background-color] duration-150 ease-out active:scale-90',
                        showNote && 'bg-foreground/[0.07] text-foreground',
                        AUTH_PRESSABLE,
                    )}
                >
                    <NotebookPen className="size-4" aria-hidden="true" />
                </button>
            </div>
            {entry.category === ALLERGY && (
                <div
                    role="radiogroup"
                    aria-label={t('onboarding.dietary_preferences.severity')}
                    className="grid h-10 auto-cols-fr grid-flow-col rounded-full bg-foreground/[0.05] p-1 dark:bg-white/[0.06]"
                >
                    {severityOptions.map((option) => {
                        const isChecked = entry.severity === option.value;

                        return (
                            <button
                                key={option.value}
                                type="button"
                                role="radio"
                                aria-checked={isChecked}
                                onClick={() =>
                                    onChange(
                                        'severity',
                                        isChecked ? '' : option.value,
                                    )
                                }
                                className={cn(
                                    'rounded-full text-[0.8125rem] font-semibold transition-[background-color,color] duration-150 ease-out',
                                    isChecked
                                        ? option.value === 'severe'
                                            ? 'bg-rose-500 text-white'
                                            : 'bg-background text-foreground shadow-[0_1px_2px_rgb(0_0_0/0.1)] dark:bg-neutral-700'
                                        : 'text-muted-foreground',
                                    AUTH_PRESSABLE,
                                )}
                            >
                                {t(
                                    `onboarding.dietary_preferences.severity_${option.value}`,
                                    option.label,
                                )}
                            </button>
                        );
                    })}
                </div>
            )}
            {showNote && (
                <input
                    type="text"
                    autoFocus={entry.notes === ''}
                    maxLength={500}
                    aria-label={t('onboarding.dietary_preferences.notes')}
                    placeholder={t(
                        'onboarding.dietary_preferences.notes_placeholder',
                    )}
                    value={entry.notes}
                    onChange={(event) => onChange('notes', event.target.value)}
                    className="h-11 w-full rounded-full bg-background px-4 text-base text-foreground outline-none placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-emerald-600 dark:bg-white/[0.08]"
                />
            )}
        </div>
    );
}

function Section({
    order,
    label,
    children,
}: {
    order: number;
    label: string;
    children: ReactNode;
}) {
    return (
        <section
            style={{ animationDelay: `${order * 45}ms` }}
            className={ONBOARDING_RISE}
        >
            <h2 className={ONBOARDING_LABEL}>{label}</h2>
            {children}
        </section>
    );
}

export default function DietaryPreferences({
    existingAttributes,
    categories,
    severityOptions,
}: Props) {
    const { t } = useTranslation('common');
    const [attributes, setAttributes] = useState<AttributeEntry[]>(() =>
        existingAttributes.map((attribute) => ({
            category: attribute.category,
            value: attribute.value,
            severity: attribute.severity ?? '',
            notes: attribute.notes ?? '',
        })),
    );

    const isEntry = (entry: AttributeEntry, category: string, value: string) =>
        entry.category === category && isSame(entry.value, value);

    const add = (category: string, value: string) => {
        if (
            value.trim() === '' ||
            attributes.some((entry) => isEntry(entry, category, value))
        ) {
            return;
        }

        setAttributes((current) => [
            ...current,
            { category, value: value.trim(), severity: '', notes: '' },
        ]);
    };

    const toggle = (category: string, value: string) =>
        attributes.some((entry) => isEntry(entry, category, value))
            ? setAttributes((current) =>
                  current.filter((entry) => !isEntry(entry, category, value)),
              )
            : add(category, value);

    const update = (
        target: AttributeEntry,
        field: 'severity' | 'notes',
        value: string,
    ) =>
        setAttributes((current) =>
            current.map((entry) =>
                isEntry(entry, target.category, target.value)
                    ? { ...entry, [field]: value }
                    : entry,
            ),
        );

    const sections = categories.filter(
        (category) => category.value in CATEGORY_DETAILS,
    );
    const section = (category: CategoryOption, order: number) => {
        const selected = attributes.filter(
            (entry) => entry.category === category.value,
        );

        return (
            <Section
                key={category.value}
                order={order}
                label={t(
                    CATEGORY_DETAILS[category.value].label,
                    category.label,
                )}
            >
                <CategoryChips
                    suggestions={CATEGORY_DETAILS[category.value].suggestions}
                    selected={selected}
                    onToggle={(value) => toggle(category.value, value)}
                    onAdd={(value) => add(category.value, value)}
                />
                {category.value === ALLERGY && selected.length > 0 && (
                    <div className="mt-3 grid gap-2">
                        {selected.map((entry) => (
                            <DetailRow
                                key={entry.value}
                                entry={entry}
                                severityOptions={severityOptions}
                                onChange={(field, value) =>
                                    update(entry, field, value)
                                }
                            />
                        ))}
                    </div>
                )}
            </Section>
        );
    };
    const others = attributes.filter(
        (entry) =>
            entry.category !== ALLERGY && entry.category in CATEGORY_DETAILS,
    );

    return (
        <AuthScreen
            title={t('onboarding.dietary_preferences.title')}
            className="md:min-h-0 md:max-w-[54rem]"
        >
            <OnboardingHeader
                step={3}
                backHref={onboarding.identity.show.url()}
            />
            <Form
                {...onboarding.dietary.store.form()}
                noValidate
                transform={() => ({
                    attributes: attributes.map((entry) => ({
                        ...entry,
                        severity: entry.severity || null,
                        notes: entry.notes || null,
                    })),
                })}
                disableWhileProcessing
                className={ONBOARDING_COLUMNS}
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-7">
                            <div className={ONBOARDING_RISE}>
                                <h1 className={AUTH_HEADLINE}>
                                    {t(
                                        'onboarding.dietary_preferences.heading',
                                    )}
                                </h1>
                                <p className={AUTH_LEAD}>
                                    {t(
                                        'onboarding.dietary_preferences.description',
                                    )}
                                </p>
                            </div>
                            {sections
                                .filter(
                                    (category) => category.value === ALLERGY,
                                )
                                .map((category) => section(category, 1))}
                        </div>

                        <div className="grid gap-7 md:pt-2">
                            {sections
                                .filter(
                                    (category) => category.value !== ALLERGY,
                                )
                                .map((category, index) =>
                                    section(category, index + 2),
                                )}

                            {others.length > 0 && (
                                <Section
                                    order={sections.length + 1}
                                    label={t(
                                        'onboarding.dietary_preferences.notes_heading',
                                    )}
                                >
                                    <div className="grid gap-2">
                                        {others.map((entry) => (
                                            <DetailRow
                                                key={`${entry.category}-${entry.value}`}
                                                entry={entry}
                                                severityOptions={
                                                    severityOptions
                                                }
                                                onChange={(field, value) =>
                                                    update(entry, field, value)
                                                }
                                            />
                                        ))}
                                    </div>
                                </Section>
                            )}

                            <div className="grid gap-3">
                                <AuthFieldError
                                    message={Object.values(errors)[0]}
                                    className="text-center"
                                />
                                <AuthSubmitButton processing={processing}>
                                    {t(
                                        'onboarding.dietary_preferences.complete',
                                    )}
                                </AuthSubmitButton>
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </AuthScreen>
    );
}
