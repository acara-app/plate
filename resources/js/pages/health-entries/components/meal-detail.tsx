import UpdateHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/UpdateHealthEntryController';
import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    formatLocalDatetime,
    localDatetimeToIso,
} from '@/lib/format-local-datetime';
import {
    getReadingGroups,
    getTypeConfig,
    mealTitle,
    SOURCE_META,
    userNotes,
} from '@/lib/health-entry-registry';
import {
    getMealTypeConfig,
    MEAL_TYPES,
    mealTypeForTime,
} from '@/lib/meal-types';
import { useUnsavedChanges } from '@/hooks/use-unsaved-changes';
import { cn } from '@/lib/utils';
import type { HealthEntry, LogTypeValue } from '@/types/diabetes';
import type { MealType } from '@/types/meal-plan';
import { router, useForm } from '@inertiajs/react';
import { ChevronRight, Pencil, Plus, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import DetailActions from './detail-actions';
import DetailTopBar from './detail-top-bar';
import BottomSheet from './bottom-sheet';
import IngredientForm, { type EditableItem } from './ingredient-form';
import {
    CALORIES,
    formatAmount,
    MACROS,
    type Nutrition,
    type NutritionField,
} from './macros';
import DetailsForm from './meal-details-form';
import PhotoHero from './photo-hero';
import ValueForm from './value-form';

type MealForm = {
    log_type: 'food';
    measured_at: string;
    notes: string;
    meal_type: MealType;
    items: EditableItem[];
} & Nutrition;

const MAX_ITEMS = 30;

export default function MealDetail({
    entry,
    backUrl,
    glucoseUnit,
    onEditReading,
}: {
    entry: HealthEntry;
    backUrl: string;
    glucoseUnit: string;
    onEditReading: (type: LogTypeValue) => void;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const analysis = entry.food_analysis ?? null;
    const isItemized = (analysis?.items.length ?? 0) > 0;
    const savedMeasuredAt = formatLocalDatetime(new Date(entry.measured_at));
    const savedNotes = userNotes(entry) ?? '';
    const form = useForm<MealForm>({
        log_type: 'food',
        measured_at: savedMeasuredAt,
        notes: savedNotes,
        meal_type:
            entry.meal_type ?? mealTypeForTime(new Date(entry.measured_at)),
        items: (analysis?.items ?? []).map((item, index) => ({
            ...item,
            grams: item.grams ?? null,
            key: `item-${index}`,
            originalName: item.name,
        })),
        calories: entry.calories,
        protein_grams: entry.protein_grams,
        carbs_grams: entry.carbs_grams,
        fat_grams: entry.fat_grams,
        fiber_grams: entry.fiber_grams,
    });
    const [sheet, setSheet] = useState<
        'ingredient' | 'value' | 'details' | null
    >(null);
    const [sheetKey, setSheetKey] = useState(0);
    const [ingredientIndex, setIngredientIndex] = useState<number | null>(null);
    const [valueField, setValueField] = useState<NutritionField>('calories');
    const leaveGuard = useUnsavedChanges(form.isDirty);

    useEffect(() => {
        form.setDefaults({ measured_at: savedMeasuredAt, notes: savedNotes });
        form.setData((current) => ({
            ...current,
            measured_at: savedMeasuredAt,
            notes: savedNotes,
        }));
    }, [savedMeasuredAt, savedNotes]);

    const { data } = form;
    const errors = form.errors as Record<string, string>;
    const items = data.items;
    const totals: Nutrition = isItemized
        ? {
              calories: items.reduce((sum, item) => sum + item.calories, 0),
              protein_grams: items.reduce((sum, item) => sum + item.protein, 0),
              carbs_grams: items.reduce((sum, item) => sum + item.carbs, 0),
              fat_grams: items.reduce((sum, item) => sum + item.fat, 0),
              fiber_grams: items.some((item) => item.fiber != null)
                  ? items.reduce((sum, item) => sum + (item.fiber ?? 0), 0)
                  : null,
          }
        : data;
    const measuredAt = new Date(data.measured_at);
    const mealType = getMealTypeConfig(data.meal_type);
    const readingGroups = getReadingGroups(entry, glucoseUnit, t);
    const source = entry.source ? SOURCE_META[entry.source] : undefined;
    const title = mealTitle(items, data.notes, t);
    const itemErrors = (index: number): Record<string, string> => {
        const prefix = `items.${index}.`;

        return Object.fromEntries(
            Object.entries(errors)
                .filter(([key]) => key.startsWith(prefix))
                .map(([key, message]) => [key.slice(prefix.length), message]),
        );
    };
    const generalErrors = [
        ...new Set(
            Object.entries(errors)
                .filter(([key]) => !key.startsWith('items.'))
                .map(([, message]) => message),
        ),
    ];
    const hasItemErrors = Object.keys(errors).some((key) =>
        key.startsWith('items.'),
    );

    const openSheet = (kind: 'ingredient' | 'value' | 'details') => {
        setSheetKey((key) => key + 1);
        setSheet(kind);
    };

    const closeSheet = () => setSheet(null);

    const openIngredient = (index: number | null) => {
        setIngredientIndex(index);
        openSheet('ingredient');
    };

    const applyIngredient = (
        item: Omit<EditableItem, 'key' | 'originalName'>,
    ) => {
        form.setData(
            'items',
            ingredientIndex === null
                ? [
                      ...items,
                      {
                          ...item,
                          key: `new-${sheetKey}`,
                          originalName: item.name,
                      },
                  ]
                : items.map((current, index) =>
                      index === ingredientIndex
                          ? { ...current, ...item }
                          : current,
                  ),
        );
        form.clearErrors();
        closeSheet();
    };

    const removeIngredient = () => {
        form.setData(
            'items',
            items.filter((_, index) => index !== ingredientIndex),
        );
        form.clearErrors();
        closeSheet();
    };

    const openValue = (field: NutritionField) => {
        setValueField(field);
        openSheet('value');
    };

    const save = () => {
        form.transform((current) => {
            const notes = current.notes.trim();
            const base = {
                log_type: current.log_type,
                meal_type: current.meal_type,
                measured_at: localDatetimeToIso(current.measured_at),
                notes: notes === '' ? null : notes,
            };

            return isItemized
                ? {
                      ...base,
                      items: current.items.map((item) => ({
                          name: item.name,
                          portion: item.portion,
                          grams: item.grams ?? null,
                          calories: item.calories,
                          protein: item.protein,
                          carbs: item.carbs,
                          fat: item.fat,
                          fiber: item.fiber ?? null,
                          provenance: item.provenance,
                      })),
                  }
                : {
                      ...base,
                      calories: current.calories,
                      protein_grams: current.protein_grams,
                      carbs_grams: current.carbs_grams,
                      fat_grams: current.fat_grams,
                      fiber_grams: current.fiber_grams,
                  };
        });

        form.put(UpdateHealthEntryController.url(entry.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                leaveGuard.allowLeaving();
                router.visit(
                    ListHealthEntryController({
                        query: { date: String(page.props.date) },
                    }),
                );
            },
        });
    };

    const caloriesLabel = t('health_entries.nutrition.calories');
    const valueConfig = MACROS.find((macro) => macro.field === valueField);
    const valueLabel = valueConfig ? t(valueConfig.labelKey) : caloriesLabel;

    return (
        <div className="mx-auto flex w-full max-w-xl min-w-0 flex-col gap-6 px-4 pt-3 pb-36 sm:px-6 md:pb-10">
            <DetailTopBar entry={entry} backUrl={backUrl} />
            <h1 className="-mt-3 text-4xl font-bold tracking-tight">
                {t('health_entries.detail.nutrition')}
            </h1>
            {entry.photo_url && (
                <PhotoHero
                    src={entry.photo_url}
                    measuredAt={entry.measured_at}
                />
            )}
            <div className="flex items-start gap-4">
                <span
                    aria-hidden="true"
                    className={cn(
                        'flex size-14 shrink-0 items-center justify-center rounded-2xl',
                        mealType.color,
                    )}
                >
                    <mealType.Icon
                        className={cn('size-6', mealType.iconColor)}
                    />
                </span>
                <div className="flex min-w-0 flex-col gap-1.5">
                    <h2 className="text-2xl leading-tight font-semibold tracking-tight break-words">
                        {title}
                    </h2>
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                        <span className="rounded-full bg-card px-2.5 py-0.5 text-xs font-medium text-foreground ring-1 ring-border/60">
                            {t(mealType.labelKey)}
                        </span>
                        <time
                            dateTime={entry.measured_at}
                            className="tabular-nums"
                        >
                            {measuredAt.toLocaleTimeString(locale, {
                                hour: 'numeric',
                                minute: '2-digit',
                            })}
                        </time>
                        {isItemized && (
                            <span>
                                {t('health_entries.detail.items_count', {
                                    count: items.length,
                                })}
                            </span>
                        )}
                    </div>
                </div>
            </div>
            <div
                className={cn(
                    'flex items-center gap-4 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60 sm:p-6',
                    errors.calories && 'ring-2 ring-destructive',
                )}
            >
                <CALORIES.Icon
                    className={cn('size-9 shrink-0', CALORIES.icon)}
                    aria-hidden="true"
                />
                <p className="flex min-w-0 flex-1 items-baseline gap-2">
                    <span className="sr-only">{caloriesLabel}</span>
                    <span className="text-5xl font-bold tracking-tight tabular-nums">
                        {totals.calories === null
                            ? '—'
                            : formatAmount(totals.calories, locale)}
                    </span>
                    <span className="text-lg text-muted-foreground">
                        {t('health_entries.nutrition.kcal')}
                    </span>
                </p>
                {!isItemized && (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-11 shrink-0 rounded-full text-muted-foreground"
                        aria-label={t('health_entries.detail.edit_value', {
                            label: caloriesLabel,
                        })}
                        onClick={() => openValue('calories')}
                    >
                        <Pencil aria-hidden="true" />
                    </Button>
                )}
            </div>
            <dl
                className={cn(
                    'grid gap-2 sm:gap-3',
                    isItemized ? 'grid-cols-4' : 'grid-cols-2 sm:grid-cols-4',
                )}
            >
                {MACROS.map((macro) => {
                    const value = totals[macro.field];

                    return (
                        <div
                            key={macro.key}
                            className={cn(
                                'relative flex min-w-0 flex-col gap-2 rounded-3xl p-3 sm:p-4',
                                macro.card,
                                errors[macro.field] &&
                                    'ring-2 ring-destructive',
                            )}
                        >
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'flex size-9 items-center justify-center rounded-full',
                                    macro.tile,
                                )}
                            >
                                <macro.Icon
                                    className={cn('size-[18px]', macro.icon)}
                                />
                            </span>
                            <div className="flex min-w-0 flex-col-reverse">
                                <dt className="truncate text-xs text-muted-foreground sm:text-sm">
                                    {t(macro.labelKey)}
                                </dt>
                                <dd className="text-lg font-semibold tabular-nums sm:text-xl">
                                    {value === null
                                        ? '—'
                                        : t('health_entries.nutrition.grams', {
                                              value: formatAmount(
                                                  value,
                                                  locale,
                                                  1,
                                              ),
                                          })}
                                </dd>
                            </div>
                            {!isItemized && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="absolute top-2 right-2 size-10 rounded-full text-muted-foreground"
                                    aria-label={t(
                                        'health_entries.detail.edit_value',
                                        { label: t(macro.labelKey) },
                                    )}
                                    onClick={() => openValue(macro.field)}
                                >
                                    <Pencil
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>
                            )}
                        </div>
                    );
                })}
            </dl>
            {isItemized && (
                <section
                    aria-labelledby="ingredients-heading"
                    className="flex flex-col gap-3"
                >
                    <h2
                        id="ingredients-heading"
                        className="text-xl font-semibold tracking-tight"
                    >
                        {t('health_entries.detail.ingredients')}
                    </h2>
                    <ul className="divide-y overflow-hidden rounded-3xl bg-card shadow-xs ring-1 ring-border/60">
                        {items.map((item, index) => {
                            const messages = Object.values(itemErrors(index));

                            return (
                                <li key={item.key}>
                                    <button
                                        type="button"
                                        onClick={() => openIngredient(index)}
                                        aria-describedby={
                                            messages.length > 0
                                                ? `ingredient-error-${index}`
                                                : undefined
                                        }
                                        className={cn(
                                            'flex min-h-16 w-full items-center gap-3 px-5 py-3 text-left transition-colors outline-none hover:bg-muted/50 focus-visible:bg-muted/60',
                                            messages.length > 0 &&
                                                'bg-destructive/5',
                                        )}
                                    >
                                        <span className="flex min-w-0 flex-1 flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-3">
                                            <span className="min-w-0 text-base break-words sm:flex-1 sm:truncate">
                                                {item.name}
                                            </span>
                                            <span className="flex min-w-0 gap-2 text-sm text-muted-foreground">
                                                <span className="shrink-0 tabular-nums">
                                                    {formatAmount(
                                                        item.calories,
                                                        locale,
                                                    )}{' '}
                                                    {t(
                                                        'health_entries.nutrition.kcal',
                                                    )}
                                                </span>
                                                {item.portion && (
                                                    <span className="min-w-0 truncate text-muted-foreground/80 sm:max-w-40">
                                                        {item.portion}
                                                    </span>
                                                )}
                                            </span>
                                        </span>
                                        <ChevronRight
                                            className="size-5 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    {messages.length > 0 && (
                                        <p
                                            id={`ingredient-error-${index}`}
                                            className="px-5 pb-3 text-sm text-destructive"
                                        >
                                            {messages[0]}
                                        </p>
                                    )}
                                </li>
                            );
                        })}
                        {items.length < MAX_ITEMS && (
                            <li>
                                <button
                                    type="button"
                                    onClick={() => openIngredient(null)}
                                    className="flex min-h-14 w-full items-center justify-center gap-2 text-base font-semibold text-primary transition-colors outline-none hover:bg-muted/50 focus-visible:bg-muted/60"
                                >
                                    <Plus
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                    {t('health_entries.detail.add_ingredient')}
                                </button>
                            </li>
                        )}
                    </ul>
                </section>
            )}
            <section
                aria-labelledby="meal-type-heading"
                className="flex flex-col gap-3"
            >
                <h2
                    id="meal-type-heading"
                    className="text-xl font-semibold tracking-tight"
                >
                    {t('health_entries.detail.meal_type')}
                </h2>
                <ToggleGroup
                    type="single"
                    value={data.meal_type}
                    onValueChange={(value) =>
                        value && form.setData('meal_type', value as MealType)
                    }
                    aria-labelledby="meal-type-heading"
                    className="grid grid-cols-4 gap-1 rounded-full bg-card p-1 shadow-xs ring-1 ring-border/60"
                >
                    {MEAL_TYPES.map((option) => (
                        <ToggleGroupItem
                            key={option.key}
                            value={option.key}
                            className="h-11 min-w-0 rounded-full px-1.5 text-xs leading-tight whitespace-normal sm:text-sm"
                        >
                            {t(option.labelKey)}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
            </section>
            {data.notes.trim() !== '' && (
                <section
                    aria-labelledby="meal-notes-heading"
                    className="flex flex-col gap-3"
                >
                    <h2
                        id="meal-notes-heading"
                        className="text-xl font-semibold tracking-tight"
                    >
                        {t('health_entries.detail.notes')}
                    </h2>
                    <p className="rounded-3xl bg-card p-5 text-sm break-words whitespace-pre-wrap shadow-xs ring-1 ring-border/60">
                        {data.notes}
                    </p>
                </section>
            )}
            {readingGroups.length > 0 && (
                <section
                    aria-labelledby="also-logged-heading"
                    className="flex flex-col gap-3"
                >
                    <h2
                        id="also-logged-heading"
                        className="text-xl font-semibold tracking-tight"
                    >
                        {t('health_entries.detail.also_logged')}
                    </h2>
                    <ul className="flex flex-col gap-3">
                        {readingGroups.map((group) => {
                            const config = getTypeConfig(group.type);

                            return (
                                <li
                                    key={group.type}
                                    className="flex items-center gap-4 rounded-3xl bg-card p-4 shadow-xs ring-1 ring-border/60"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-muted"
                                    >
                                        <config.icon
                                            className={cn(
                                                'size-5',
                                                config.accent,
                                            )}
                                        />
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col">
                                        <span className="text-sm text-muted-foreground">
                                            {t(config.labelKey)}
                                        </span>
                                        <span className="font-semibold break-words first-letter:uppercase">
                                            {group.badges
                                                .map((badge) => badge.label)
                                                .join(' · ')}
                                        </span>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-11 shrink-0 rounded-full text-muted-foreground"
                                        aria-label={t(
                                            'health_entries.detail.edit_value',
                                            { label: t(config.labelKey) },
                                        )}
                                        onClick={() =>
                                            onEditReading(group.type)
                                        }
                                    >
                                        <Pencil aria-hidden="true" />
                                    </Button>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}
            {(analysis || source) && (
                <p className="flex items-start gap-2 text-xs text-muted-foreground">
                    {analysis && (
                        <Sparkles
                            className="mt-0.5 size-3.5 shrink-0 text-primary"
                            aria-hidden="true"
                        />
                    )}
                    <span>
                        {[
                            analysis ? t('snap_to_track.title') : null,
                            !analysis && source ? t(source.labelKey) : null,
                            analysis?.confidence != null
                                ? t('snap_to_track.review.saved_confidence', {
                                      confidence: analysis.confidence,
                                  })
                                : analysis
                                  ? t('health_entries.detail.estimates_note')
                                  : null,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </span>
                </p>
            )}
            <DetailActions
                onEdit={() => openSheet('details')}
                doneUrl={backUrl}
                onDone={form.isDirty ? save : undefined}
                saving={form.processing}
                message={
                    generalErrors.length > 0 || hasItemErrors ? (
                        <div
                            role="alert"
                            className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
                        >
                            <p className="font-medium">
                                {t('health_entries.detail.save_error')}
                            </p>
                            {generalErrors.length > 0 && (
                                <ul className="mt-1 list-disc pl-5">
                                    {generalErrors.map((message) => (
                                        <li key={message}>{message}</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ) : null
                }
            />
            <BottomSheet
                open={sheet === 'ingredient'}
                onClose={closeSheet}
                title={t(
                    ingredientIndex === null
                        ? 'health_entries.detail.new_ingredient'
                        : 'health_entries.detail.edit_ingredient',
                )}
                description={t('health_entries.detail.ingredient_description')}
            >
                <IngredientForm
                    key={sheetKey}
                    item={
                        ingredientIndex === null
                            ? null
                            : (items[ingredientIndex] ?? null)
                    }
                    canRemove={ingredientIndex !== null && items.length > 1}
                    errors={
                        ingredientIndex === null
                            ? {}
                            : itemErrors(ingredientIndex)
                    }
                    onApply={applyIngredient}
                    onRemove={removeIngredient}
                />
            </BottomSheet>
            <BottomSheet
                open={sheet === 'value'}
                onClose={closeSheet}
                title={t('health_entries.detail.edit_value', {
                    label: valueLabel,
                })}
                description={t('health_entries.detail.value_description')}
            >
                <ValueForm
                    key={sheetKey}
                    label={valueLabel}
                    unit={
                        valueConfig
                            ? t('health_entries.nutrition.grams_unit')
                            : t('health_entries.nutrition.kcal')
                    }
                    value={data[valueField]}
                    integer={valueField === 'calories'}
                    error={errors[valueField]}
                    onApply={(value) => {
                        form.setData(valueField, value);
                        form.clearErrors(valueField);
                        closeSheet();
                    }}
                />
            </BottomSheet>
            <BottomSheet
                open={sheet === 'details'}
                onClose={closeSheet}
                title={t('health_entries.detail.details_title')}
                description={t('health_entries.detail.details_description')}
            >
                <DetailsForm
                    key={sheetKey}
                    details={{
                        measured_at: data.measured_at,
                        notes: data.notes,
                    }}
                    errors={errors}
                    onApply={(details) => {
                        form.setData((current) => ({ ...current, ...details }));
                        form.clearErrors('measured_at', 'notes');
                        closeSheet();
                    }}
                />
            </BottomSheet>
            <AlertDialog
                open={leaveGuard.pendingUrl !== null}
                onOpenChange={(open) => !open && leaveGuard.keepEditing()}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('health_entries.detail.discard_title')}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {t('health_entries.detail.discard_description')}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>
                            {t('health_entries.detail.keep_editing')}
                        </AlertDialogCancel>
                        <Button
                            variant="destructive"
                            onClick={leaveGuard.discard}
                        >
                            {t('health_entries.detail.discard')}
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
