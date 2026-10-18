import InputError from '@/components/input-error';
import { RenamedItemPrompt } from '@/components/snap-to-track/renamed-item-prompt';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    needsNutritionUpdate,
    useFoodItemEstimate,
} from '@/hooks/use-food-item-estimate';
import type { FoodBreakdown, FoodBreakdownItem } from '@/types/diabetes';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import MealNutrition from './meal-nutrition';
import { useTranslation } from 'react-i18next';

const macroFields = ['calories', 'protein', 'carbs', 'fat'] as const;

type EditableFoodItem = FoodBreakdownItem & {
    originalName: string;
    estimatedFor?: string;
    estimateFailed?: boolean;
    estimatedTypical?: boolean;
};

export default function FoodBreakdownDetails({
    analysis,
}: {
    analysis: FoodBreakdown;
}) {
    const { t } = useTranslation('common');

    return (
        <div className="flex flex-col gap-2 text-sm">
            <ul className="flex flex-col gap-3">
                {analysis.items.map((item, index) => (
                    <li key={index} className="flex flex-col gap-1">
                        <p>
                            <span className="font-medium">{item.name}</span>
                            {item.portion && ` · ${item.portion}`}
                        </p>
                        <dl className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            {macroFields.map((field) => (
                                <div key={field} className="flex gap-1">
                                    <dt>
                                        {t(`snap_to_track.review.${field}`)}
                                    </dt>
                                    <dd>{item[field]}</dd>
                                </div>
                            ))}
                        </dl>
                    </li>
                ))}
            </ul>
            {analysis.confidence !== null && (
                <p className="text-xs text-muted-foreground">
                    {t('snap_to_track.review.saved_confidence', {
                        confidence: analysis.confidence,
                    })}
                </p>
            )}
        </div>
    );
}

export function FoodBreakdownFields({
    analysis,
    errors,
    onEstimatingChange,
}: {
    analysis: FoodBreakdown;
    errors: Record<string, string>;
    onEstimatingChange: (estimating: boolean) => void;
}) {
    const { t } = useTranslation('common');
    const [items, setItems] = useState<EditableFoodItem[]>(() =>
        analysis.items.map((item) => ({
            ...item,
            grams: item.grams ?? null,
            originalName: item.name,
        })),
    );
    const { estimatingIndex, estimate } = useFoodItemEstimate();

    useEffect(() => {
        onEstimatingChange(estimatingIndex !== null);
    }, [estimatingIndex, onEstimatingChange]);

    const totals = items.reduce(
        (total, item) => ({
            calories: total.calories + Number(item.calories || 0),
            protein_grams: total.protein_grams + Number(item.protein || 0),
            carbs_grams: total.carbs_grams + Number(item.carbs || 0),
            fat_grams: total.fat_grams + Number(item.fat || 0),
        }),
        { calories: 0, protein_grams: 0, carbs_grams: 0, fat_grams: 0 },
    );

    const updateItem = (
        index: number,
        field: keyof FoodBreakdownItem,
        value: string,
    ) => {
        setItems((current) =>
            current.map((item, itemIndex) =>
                itemIndex === index
                    ? {
                          ...item,
                          [field]: value,
                          provenance: 'user',
                          estimateFailed: false,
                      }
                    : item,
            ),
        );
    };

    const updateNutrition = (index: number) => {
        const item = items[index];

        if (item === undefined) {
            return;
        }

        const name = item.name.trim();
        const grams = item.grams ?? null;

        estimate(index, name, grams, {
            onSuccess: (response) => {
                setItems((current) =>
                    current.map((row, rowIndex) =>
                        rowIndex === index && row.name.trim() === name
                            ? {
                                  ...row,
                                  portion: response.portion,
                                  calories: response.calories,
                                  protein: response.protein,
                                  carbs: response.carbs,
                                  fat: response.fat,
                                  grams: response.grams,
                                  provenance: 'user',
                                  estimatedFor: name,
                                  estimateFailed: false,
                                  estimatedTypical: grams === null,
                              }
                            : row,
                    ),
                );
            },
            onFailed: () =>
                setItems((current) =>
                    current.map((row, rowIndex) =>
                        rowIndex === index
                            ? { ...row, estimateFailed: true }
                            : row,
                    ),
                ),
        });
    };

    return (
        <div className="flex flex-col gap-6">
            <div
                className="sticky -top-6 bg-background py-4"
                role="status"
                aria-label={t('health_entries.timeline.meal_totals')}
            >
                <p className="mb-2 text-xs font-medium text-muted-foreground">
                    {t('health_entries.timeline.meal_totals')}
                </p>
                <MealNutrition entry={totals} />
            </div>
            {items.map((item, index) => {
                const hasNutritionErrors = macroFields.some((field) =>
                    Boolean(errors[`items.${index}.${field}`]),
                );
                return (
                    <fieldset
                        key={index}
                        disabled={estimatingIndex !== null}
                        className="flex min-w-0 flex-col gap-3"
                    >
                        <legend className="mb-3 text-sm font-medium">
                            {t('health_entries.timeline.food_number', {
                                number: index + 1,
                            })}
                        </legend>
                        <input
                            type="hidden"
                            name={`items[${index}][provenance]`}
                            value={item.provenance}
                        />
                        <input
                            type="hidden"
                            name={`items[${index}][grams]`}
                            value={item.grams ?? ''}
                        />
                        <div className="grid gap-3 sm:grid-cols-2">
                            {(['name', 'portion'] as const).map((field) => (
                                <div
                                    key={field}
                                    className="flex min-w-0 flex-col gap-2"
                                >
                                    <Label
                                        htmlFor={`saved-item-${index}-${field}`}
                                    >
                                        {t(
                                            `snap_to_track.review.${field === 'name' ? 'item_name' : field}`,
                                        )}
                                    </Label>
                                    <Input
                                        id={`saved-item-${index}-${field}`}
                                        name={`items[${index}][${field}]`}
                                        value={item[field] ?? ''}
                                        required={field === 'name'}
                                        maxLength={100}
                                        aria-invalid={Boolean(
                                            errors[`items.${index}.${field}`],
                                        )}
                                        onChange={(event) =>
                                            updateItem(
                                                index,
                                                field,
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors[`items.${index}.${field}`]
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                        {needsNutritionUpdate(
                            item.name,
                            item.originalName,
                            item.estimatedFor,
                        ) && (
                            <RenamedItemPrompt
                                originalName={item.originalName}
                                name={item.name}
                                estimating={estimatingIndex === index}
                                disabled={estimatingIndex !== null}
                                failed={item.estimateFailed === true}
                                onUpdate={() => updateNutrition(index)}
                            />
                        )}
                        {item.estimatedTypical && (
                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'snap_to_track.review.rename.typical_portion',
                                )}
                            </p>
                        )}
                        <InputError message={errors[`items.${index}.grams`]} />
                        <details
                            className="group"
                            open={hasNutritionErrors || undefined}
                            onInvalidCapture={(event) => {
                                event.currentTarget.open = true;
                            }}
                        >
                            <summary className="flex min-h-10 cursor-pointer list-none items-center gap-2 text-sm text-muted-foreground [&::-webkit-details-marker]:hidden">
                                <ChevronDown
                                    className="size-4 transition-transform group-open:rotate-180"
                                    aria-hidden="true"
                                />
                                {t('health_entries.timeline.nutrition_details')}
                            </summary>
                            <div className="grid grid-cols-2 gap-3 pt-2 sm:grid-cols-4">
                                {macroFields.map((field) => (
                                    <div
                                        key={field}
                                        className="flex min-w-0 flex-col gap-2"
                                    >
                                        <Label
                                            htmlFor={`saved-item-${index}-${field}`}
                                        >
                                            {t(`snap_to_track.review.${field}`)}
                                        </Label>
                                        <Input
                                            id={`saved-item-${index}-${field}`}
                                            name={`items[${index}][${field}]`}
                                            type="number"
                                            inputMode="decimal"
                                            value={item[field]}
                                            min={0}
                                            step="0.1"
                                            aria-invalid={Boolean(
                                                errors[
                                                    `items.${index}.${field}`
                                                ],
                                            )}
                                            onChange={(event) =>
                                                updateItem(
                                                    index,
                                                    field,
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `items.${index}.${field}`
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        </details>
                    </fieldset>
                );
            })}
            <InputError message={errors.items} />
        </div>
    );
}
