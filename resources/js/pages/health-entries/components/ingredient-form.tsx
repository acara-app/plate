import InputError from '@/components/input-error';
import { RenamedItemPrompt } from '@/components/snap-to-track/renamed-item-prompt';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    estimatedFields,
    needsNutritionUpdate,
    useFoodItemEstimate,
} from '@/hooks/use-food-item-estimate';
import { cn } from '@/lib/utils';
import type { FoodBreakdownItem } from '@/types/diabetes';
import { Sparkles, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';

export type EditableItem = FoodBreakdownItem & {
    key: string;
    originalName: string;
    estimatedFor?: string;
};

type NutrientField = 'calories' | 'protein' | 'carbs' | 'fat' | 'fiber';

type Draft = {
    name: string;
    portion: string;
    grams: number | null;
    provenance: string;
    estimatedFor?: string;
    estimateFailed?: boolean;
    estimatedTypical?: boolean;
} & Record<NutrientField, string>;

const NUTRIENTS: { field: NutrientField; labelKey: string }[] = [
    { field: 'calories', labelKey: 'snap_to_track.review.calories' },
    { field: 'protein', labelKey: 'snap_to_track.review.protein' },
    { field: 'carbs', labelKey: 'snap_to_track.review.carbs' },
    { field: 'fat', labelKey: 'snap_to_track.review.fat' },
    { field: 'fiber', labelKey: 'snap_to_track.review.fiber' },
];

function toDraft(item: EditableItem | null): Draft {
    return {
        name: item?.name ?? '',
        portion: item?.portion ?? '',
        grams: item?.grams ?? null,
        provenance: item?.provenance ?? 'user',
        estimatedFor: item?.estimatedFor,
        calories: item ? String(item.calories) : '',
        protein: item ? String(item.protein) : '',
        carbs: item ? String(item.carbs) : '',
        fat: item ? String(item.fat) : '',
        fiber: item?.fiber != null ? String(item.fiber) : '',
    };
}

const toNumber = (value: string): number => {
    const number = Number(value);

    return Number.isFinite(number) && number >= 0 ? number : 0;
};

export default function IngredientForm({
    item,
    canRemove,
    errors,
    onApply,
    onRemove,
}: {
    item: EditableItem | null;
    canRemove: boolean;
    errors: Record<string, string>;
    onApply: (item: Omit<EditableItem, 'key' | 'originalName'>) => void;
    onRemove: () => void;
}) {
    const { t } = useTranslation('common');
    const [draft, setDraft] = useState<Draft>(() => toDraft(item));
    const { estimatingIndex, estimate } = useFoodItemEstimate();
    const estimating = estimatingIndex !== null;
    const name = draft.name.trim();
    const isNew = item === null;
    const renamed = needsNutritionUpdate(
        draft.name,
        item?.originalName,
        draft.estimatedFor,
    );
    const canEstimateNew =
        isNew &&
        name !== '' &&
        name.toLowerCase() !== (draft.estimatedFor ?? '').toLowerCase();

    const update = (field: keyof Draft, value: string) =>
        setDraft((current) => ({
            ...current,
            [field]: value,
            provenance: 'user',
            estimateFailed: false,
        }));

    const updateNutrition = () => {
        estimate(0, name, isNew ? null : draft.grams, {
            onSuccess: (response) =>
                setDraft((current) =>
                    current.name.trim() === name
                        ? {
                              ...current,
                              ...estimatedFields(response, current.fiber),
                              estimatedFor: name,
                              estimateFailed: false,
                              estimatedTypical: isNew || draft.grams === null,
                          }
                        : current,
                ),
            onFailed: () =>
                setDraft((current) => ({ ...current, estimateFailed: true })),
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (estimating || name === '') {
            return;
        }

        onApply({
            name,
            portion: draft.portion.trim() === '' ? null : draft.portion.trim(),
            grams: draft.grams,
            provenance: draft.provenance,
            estimatedFor: draft.estimatedFor,
            calories: toNumber(draft.calories),
            protein: toNumber(draft.protein),
            carbs: toNumber(draft.carbs),
            fat: toNumber(draft.fat),
            fiber: draft.fiber.trim() === '' ? null : toNumber(draft.fiber),
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5 px-5 pb-6">
            <fieldset disabled={estimating} className="flex flex-col gap-5">
                <div className="flex flex-col gap-2">
                    <Label htmlFor="ingredient-name">
                        {t('snap_to_track.review.item_name')}
                    </Label>
                    <Input
                        id="ingredient-name"
                        value={draft.name}
                        required
                        maxLength={100}
                        autoFocus={isNew}
                        className="h-12 rounded-xl text-base"
                        onChange={(event) => update('name', event.target.value)}
                    />
                    <InputError message={errors.name} />
                </div>
                {renamed && item && (
                    <RenamedItemPrompt
                        originalName={item.originalName}
                        name={draft.name}
                        estimating={estimating}
                        disabled={estimating}
                        failed={draft.estimateFailed === true}
                        onUpdate={updateNutrition}
                    />
                )}
                {canEstimateNew && (
                    <div className="flex flex-col gap-2 rounded-2xl bg-primary/5 p-3 ring-1 ring-primary/20">
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 justify-start rounded-xl"
                            onClick={updateNutrition}
                        >
                            <Sparkles
                                className="text-primary"
                                aria-hidden="true"
                            />
                            <span className="truncate">
                                {estimating
                                    ? t('snap_to_track.review.rename.updating')
                                    : t('health_entries.detail.estimate', {
                                          name,
                                      })}
                            </span>
                        </Button>
                        {draft.estimateFailed && (
                            <p className="text-xs text-muted-foreground">
                                {t('snap_to_track.review.rename.failed')}
                            </p>
                        )}
                    </div>
                )}
                {draft.estimatedTypical && (
                    <p className="text-xs text-muted-foreground">
                        {t('snap_to_track.review.rename.typical_portion')}
                    </p>
                )}
                <div className="flex flex-col gap-2">
                    <Label htmlFor="ingredient-portion">
                        {t('snap_to_track.review.portion')}
                    </Label>
                    <Input
                        id="ingredient-portion"
                        value={draft.portion}
                        maxLength={100}
                        className="h-12 rounded-xl text-base"
                        onChange={(event) =>
                            update('portion', event.target.value)
                        }
                    />
                    <InputError message={errors.portion ?? errors.grams} />
                </div>
                <div className="grid grid-cols-2 gap-3">
                    {NUTRIENTS.map(({ field, labelKey }) => (
                        <div
                            key={field}
                            className={cn(
                                'flex min-w-0 flex-col gap-2',
                                field === 'calories' && 'col-span-2',
                            )}
                        >
                            <Label htmlFor={`ingredient-${field}`}>
                                {t(labelKey)}
                            </Label>
                            <Input
                                id={`ingredient-${field}`}
                                type="number"
                                inputMode="decimal"
                                min={0}
                                step="0.1"
                                value={draft[field]}
                                aria-invalid={Boolean(errors[field])}
                                className="h-12 rounded-xl text-base tabular-nums"
                                onChange={(event) =>
                                    update(field, event.target.value)
                                }
                            />
                            <InputError message={errors[field]} />
                        </div>
                    ))}
                </div>
            </fieldset>
            <div className="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-between">
                {canRemove ? (
                    <Button
                        type="button"
                        variant="ghost"
                        className="h-12 rounded-2xl text-destructive hover:text-destructive"
                        disabled={estimating}
                        onClick={onRemove}
                    >
                        <Trash2 aria-hidden="true" />
                        {t('health_entries.detail.remove_ingredient')}
                    </Button>
                ) : (
                    <span />
                )}
                <Button
                    type="submit"
                    className="h-12 rounded-2xl px-8 text-base"
                    disabled={estimating}
                >
                    {t('health_entries.detail.apply')}
                </Button>
            </div>
        </form>
    );
}
