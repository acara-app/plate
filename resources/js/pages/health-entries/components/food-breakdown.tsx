import InputError from '@/components/input-error';
import { RenamedItemPrompt } from '@/components/snap-to-track/renamed-item-prompt';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    needsNutritionUpdate,
    useFoodItemEstimate,
} from '@/hooks/use-food-item-estimate';
import type { FoodBreakdown, FoodBreakdownItem } from '@/types/diabetes';
import { useState } from 'react';
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
            <p className="font-medium">{t('snap_to_track.title')}</p>
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
}: {
    analysis: FoodBreakdown;
    errors: Record<string, string>;
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
            {items.map((item, index) => (
                <fieldset key={index} className="flex flex-col gap-3">
                    <legend className="mb-2 font-medium">
                        {t('snap_to_track.review.items_heading')} · {index + 1}
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
                    {(['name', 'portion', ...macroFields] as const).map(
                        (field) => {
                            const numeric = macroFields.some(
                                (macro) => macro === field,
                            );
                            const error = errors[`items.${index}.${field}`];

                            return (
                                <div
                                    key={field}
                                    className="flex flex-col gap-1"
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
                                        type={numeric ? 'number' : 'text'}
                                        value={item[field] ?? ''}
                                        required={field === 'name'}
                                        maxLength={numeric ? undefined : 100}
                                        min={numeric ? 0 : undefined}
                                        step={numeric ? '0.1' : undefined}
                                        aria-invalid={Boolean(error)}
                                        onChange={(event) =>
                                            updateItem(
                                                index,
                                                field,
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={error} />
                                    {field === 'name' &&
                                        needsNutritionUpdate(
                                            item.name,
                                            item.originalName,
                                            item.estimatedFor,
                                        ) && (
                                            <RenamedItemPrompt
                                                originalName={item.originalName}
                                                name={item.name}
                                                estimating={
                                                    estimatingIndex === index
                                                }
                                                disabled={
                                                    estimatingIndex !== null
                                                }
                                                failed={
                                                    item.estimateFailed === true
                                                }
                                                onUpdate={() =>
                                                    updateNutrition(index)
                                                }
                                            />
                                        )}
                                    {field === 'portion' &&
                                        item.estimatedTypical && (
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'snap_to_track.review.rename.typical_portion',
                                                )}
                                            </p>
                                        )}
                                </div>
                            );
                        },
                    )}
                </fieldset>
            ))}
            <InputError message={errors.items} />
        </div>
    );
}
