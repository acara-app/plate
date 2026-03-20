import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Plus, X } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { useState } from 'react';
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

const COMMON_ALLERGIES = [
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
];

export default function DietaryPreferences({
    existingAttributes,
    categories,
    severityOptions,
}: Props) {
    const { t } = useTranslation('common');

    const [attributes, setAttributes] = useState<AttributeEntry[]>(
        existingAttributes.length > 0
            ? existingAttributes.map((a) => ({
                  category: a.category,
                  value: a.value,
                  severity: a.severity ?? '',
                  notes: a.notes ?? '',
              }))
            : [],
    );

    const addAttribute = (category: string = 'allergy', value: string = '') => {
        setAttributes((prev) => [
            ...prev,
            { category, value, severity: '', notes: '' },
        ]);
    };

    const removeAttribute = (index: number) => {
        setAttributes((prev) => prev.filter((_, i) => i !== index));
    };

    const updateAttribute = (
        index: number,
        field: keyof AttributeEntry,
        value: string,
    ) => {
        setAttributes((prev) =>
            prev.map((attr, i) =>
                i === index ? { ...attr, [field]: value } : attr,
            ),
        );
    };

    const addQuickAllergy = (allergen: string) => {
        const exists = attributes.some(
            (a) =>
                a.value.toLowerCase() === allergen.toLowerCase() &&
                a.category === 'allergy',
        );

        if (!exists) {
            addAttribute('allergy', allergen);
        }
    };

    return (
        <>
            <Head title={t('onboarding.dietary_preferences.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 3,
                                    total: 3,
                                })}
                            </span>
                            <span>100%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-full overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.dietary_preferences.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.dietary_preferences.description')}
                        </p>

                        <Form
                            {...onboarding.dietary.store.form()}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <h2 className="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {t(
                                                'onboarding.dietary_preferences.allergies',
                                            )}
                                        </h2>
                                        <div className="mb-4 flex flex-wrap gap-2">
                                            {COMMON_ALLERGIES.map(
                                                (allergen) => {
                                                    const isSelected =
                                                        attributes.some(
                                                            (a) =>
                                                                a.value.toLowerCase() ===
                                                                    allergen.toLowerCase() &&
                                                                a.category ===
                                                                    'allergy',
                                                        );

                                                    return (
                                                        <button
                                                            key={allergen}
                                                            type="button"
                                                            onClick={() =>
                                                                isSelected
                                                                    ? setAttributes(
                                                                          (
                                                                              prev,
                                                                          ) =>
                                                                              prev.filter(
                                                                                  (
                                                                                      a,
                                                                                  ) =>
                                                                                      !(
                                                                                          a.value.toLowerCase() ===
                                                                                              allergen.toLowerCase() &&
                                                                                          a.category ===
                                                                                              'allergy'
                                                                                      ),
                                                                              ),
                                                                      )
                                                                    : addQuickAllergy(
                                                                          allergen,
                                                                      )
                                                            }
                                                            className={cn(
                                                                'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                                                isSelected
                                                                    ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300'
                                                                    : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
                                                            )}
                                                        >
                                                            {isSelected
                                                                ? `${allergen} ✕`
                                                                : `+ ${allergen}`}
                                                        </button>
                                                    );
                                                },
                                            )}
                                        </div>
                                    </div>

                                    {attributes.length > 0 && (
                                        <div className="space-y-3">
                                            {attributes.map((attr, index) => (
                                                <div
                                                    key={index}
                                                    className="rounded-lg border border-gray-200 p-4 dark:border-gray-600"
                                                >
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="grid flex-1 gap-3 sm:grid-cols-2">
                                                            <div>
                                                                <Select
                                                                    name={`attributes[${index}][category]`}
                                                                    value={
                                                                        attr.category
                                                                    }
                                                                    onValueChange={(
                                                                        v,
                                                                    ) =>
                                                                        updateAttribute(
                                                                            index,
                                                                            'category',
                                                                            v,
                                                                        )
                                                                    }
                                                                >
                                                                    <SelectTrigger>
                                                                        <SelectValue placeholder="Type" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {categories.map(
                                                                            (
                                                                                cat,
                                                                            ) => (
                                                                                <SelectItem
                                                                                    key={
                                                                                        cat.value
                                                                                    }
                                                                                    value={
                                                                                        cat.value
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        cat.label
                                                                                    }
                                                                                </SelectItem>
                                                                            ),
                                                                        )}
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                            <Input
                                                                name={`attributes[${index}][value]`}
                                                                value={
                                                                    attr.value
                                                                }
                                                                onChange={(e) =>
                                                                    updateAttribute(
                                                                        index,
                                                                        'value',
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="e.g. Peanuts, Lactose, Pork..."
                                                                required
                                                            />
                                                            {attr.category ===
                                                                'allergy' && (
                                                                <Select
                                                                    name={`attributes[${index}][severity]`}
                                                                    value={
                                                                        attr.severity
                                                                    }
                                                                    onValueChange={(
                                                                        v,
                                                                    ) =>
                                                                        updateAttribute(
                                                                            index,
                                                                            'severity',
                                                                            v,
                                                                        )
                                                                    }
                                                                >
                                                                    <SelectTrigger>
                                                                        <SelectValue
                                                                            placeholder={t(
                                                                                'onboarding.dietary_preferences.select_severity',
                                                                            )}
                                                                        />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {severityOptions.map(
                                                                            (
                                                                                opt,
                                                                            ) => (
                                                                                <SelectItem
                                                                                    key={
                                                                                        opt.value
                                                                                    }
                                                                                    value={
                                                                                        opt.value
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        opt.label
                                                                                    }
                                                                                </SelectItem>
                                                                            ),
                                                                        )}
                                                                    </SelectContent>
                                                                </Select>
                                                            )}
                                                            <Input
                                                                name={`attributes[${index}][notes]`}
                                                                value={
                                                                    attr.notes
                                                                }
                                                                onChange={(e) =>
                                                                    updateAttribute(
                                                                        index,
                                                                        'notes',
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder={t(
                                                                    'onboarding.dietary_preferences.notes_placeholder',
                                                                )}
                                                            />
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                removeAttribute(
                                                                    index,
                                                                )
                                                            }
                                                            className="mt-1 text-gray-400 hover:text-red-500"
                                                        >
                                                            <X className="size-5" />
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <InputError
                                        message={errors['attributes']}
                                    />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => addAttribute()}
                                        className="w-full gap-2"
                                    >
                                        <Plus className="size-4" />
                                        {t('add')}
                                    </Button>

                                    <div className="flex flex-col items-center gap-4 pt-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            {t(
                                                'onboarding.dietary_preferences.complete',
                                            )}
                                        </Button>

                                        <Link
                                            href={dashboard.url()}
                                            className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                        >
                                            {t('onboarding.biometrics.exit')}
                                        </Link>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
