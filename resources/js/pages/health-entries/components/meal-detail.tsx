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
import { getMealTypeConfig, mealTypeForTime } from '@/lib/meal-types';
import { useUnsavedChanges } from '@/hooks/use-unsaved-changes';
import { cn } from '@/lib/utils';
import type { HealthEntry, LogTypeValue } from '@/types/diabetes';
import type { MealType } from '@/types/meal-plan';
import { router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import DetailActions from './detail-actions';
import DetailTopBar from './detail-top-bar';
import BottomSheet from './bottom-sheet';
import IngredientForm, { type EditableItem } from './ingredient-form';
import {
    formatAmount,
    MACROS,
    type Nutrition,
    type NutritionField,
} from './macros';
import DetailsForm from './meal-details-form';
import MealTypeSwitch from './meal-type-switch';
import PhotoHero from './photo-hero';
import { EASE_OUT, PRESS_BUTTON, PRESSABLE } from './pressable';
import ValueForm from './value-form';

type MealForm = {
    log_type: 'food';
    measured_at: string;
    notes: string;
    meal_name: string;
    meal_type: MealType;
    items: EditableItem[];
} & Nutrition;

const MAX_ITEMS = 30;

const DETAILS_FIELDS = ['meal_name', 'measured_at', 'notes'];

const ENERGY_MACROS = MACROS.filter(
    (
        macro,
    ): macro is (typeof MACROS)[number] & {
        key: 'protein' | 'carbs' | 'fat';
    } => macro.key !== 'fiber',
);

const ROW =
    'flex w-full items-center gap-4 rounded-[1.375rem] px-4 text-left transition-[background-color,scale] duration-150 ease-out hover:bg-background focus-visible:bg-background focus-visible:ring-2 focus-visible:ring-primary/50 active:scale-[0.98] active:bg-background';

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
    const savedMealName = entry.meal_name ?? '';
    const form = useForm<MealForm>({
        log_type: 'food',
        measured_at: savedMeasuredAt,
        notes: savedNotes,
        meal_name: savedMealName,
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
        form.setDefaults({
            measured_at: savedMeasuredAt,
            notes: savedNotes,
            meal_name: savedMealName,
        });
        form.setData((current) => ({
            ...current,
            measured_at: savedMeasuredAt,
            notes: savedNotes,
            meal_name: savedMealName,
        }));
    }, [savedMeasuredAt, savedNotes, savedMealName]);

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
    const automaticTitle = mealTitle(items, data.notes, t);
    const title = data.meal_name.trim() || automaticTitle;
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

    const save = (stayOnPage = false) => {
        form.transform((current) => {
            const notes = current.notes.trim();
            const mealName = current.meal_name.trim();
            const base = {
                log_type: current.log_type,
                meal_type: current.meal_type,
                meal_name: mealName === '' ? null : mealName,
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
                if (stayOnPage) {
                    form.setDefaults();
                    closeSheet();

                    return;
                }

                leaveGuard.allowLeaving();
                router.visit(
                    ListHealthEntryController({
                        query: { date: String(page.props.date) },
                    }),
                );
            },
            onError: (saveErrors) => {
                if (
                    Object.keys(saveErrors).some(
                        (key) => !DETAILS_FIELDS.includes(key),
                    )
                ) {
                    closeSheet();
                }
            },
        });
    };

    const caloriesLabel = t('health_entries.nutrition.calories');
    const valueConfig = MACROS.find((macro) => macro.field === valueField);
    const valueLabel = valueConfig ? t(valueConfig.labelKey) : caloriesLabel;

    const energy = {
        protein: (totals.protein_grams ?? 0) * 4,
        carbs: (totals.carbs_grams ?? 0) * 4,
        fat: (totals.fat_grams ?? 0) * 9,
    };
    const energyTotal = energy.protein + energy.carbs + energy.fat;

    const meta = (onPhoto: boolean) => (
        <div
            className={cn(
                'flex flex-wrap items-center gap-x-3 gap-y-1 text-sm',
                onPhoto ? 'text-white/85' : 'text-muted-foreground',
            )}
        >
            <span
                className={cn(
                    'flex items-center gap-1.5 rounded-full py-1 pr-3 pl-2 text-xs font-semibold',
                    onPhoto
                        ? 'bg-white/20 text-white backdrop-blur-md'
                        : mealType.color,
                )}
            >
                <mealType.Icon className="size-3.5" aria-hidden="true" />
                {t(mealType.labelKey)}
            </span>
            <time dateTime={entry.measured_at} className="tabular-nums">
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
    );

    return (
        <div className="mx-auto flex w-full max-w-xl min-w-0 flex-col pb-36 md:pb-10">
            {entry.photo_url ? (
                <header className="relative shrink-0 sm:px-6 sm:pt-4">
                    <PhotoHero
                        src={entry.photo_url}
                        measuredAt={entry.measured_at}
                        className="max-h-[60svh] sm:rounded-[2rem]"
                    >
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-linear-to-b from-black/40 to-transparent"
                        />
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 bottom-0 h-2/3 bg-linear-to-t from-black/80 via-black/35 to-transparent"
                        />
                        <div className="pointer-events-none absolute inset-x-0 bottom-0 flex flex-col gap-2.5 px-5 pb-12 text-white sm:px-6 sm:pb-6">
                            <h1 className="line-clamp-3 text-[1.625rem] leading-[1.15] font-bold tracking-tight text-balance break-words">
                                {title}
                            </h1>
                            {meta(true)}
                        </div>
                    </PhotoHero>
                    <div className="absolute inset-x-3 top-3 sm:inset-x-9 sm:top-7">
                        <DetailTopBar entry={entry} backUrl={backUrl} overlay />
                    </div>
                </header>
            ) : (
                <header className="flex flex-col gap-3 px-4 pt-3 sm:px-6">
                    <DetailTopBar entry={entry} backUrl={backUrl} />
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
                        <div className="flex min-w-0 flex-col gap-2">
                            <h1 className="line-clamp-3 text-2xl leading-tight font-bold tracking-tight text-balance break-words">
                                {title}
                            </h1>
                            {meta(false)}
                        </div>
                    </div>
                </header>
            )}
            <div
                className={cn(
                    'relative flex flex-col gap-8 bg-background px-4 pt-7 fill-mode-both motion-safe:duration-300 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-3 sm:px-6',
                    entry.photo_url &&
                        '-mt-7 rounded-t-[1.75rem] sm:mt-0 sm:rounded-none',
                    EASE_OUT,
                )}
            >
                <section
                    aria-label={t('health_entries.detail.nutrition')}
                    className="flex flex-col gap-4"
                >
                    <div
                        className={cn(
                            'flex items-end justify-between gap-3 rounded-2xl',
                            errors.calories &&
                                'ring-2 ring-destructive ring-offset-8 ring-offset-background',
                        )}
                    >
                        <p className="flex min-w-0 items-baseline gap-2">
                            <span className="sr-only">{caloriesLabel}</span>
                            <span className="text-6xl leading-none font-bold tracking-tighter tabular-nums">
                                {totals.calories === null
                                    ? '—'
                                    : formatAmount(totals.calories, locale)}
                            </span>
                            <span className="text-lg text-muted-foreground">
                                {t('health_entries.nutrition.kcal')}
                            </span>
                        </p>
                        {!isItemized && (
                            <button
                                type="button"
                                aria-label={t(
                                    'health_entries.detail.edit_value',
                                    { label: caloriesLabel },
                                )}
                                onClick={() => openValue('calories')}
                                className={cn(
                                    'flex size-11 shrink-0 items-center justify-center rounded-full bg-muted/70 text-muted-foreground transition-[scale] duration-150 ease-out focus-visible:ring-2 focus-visible:ring-primary/50 active:scale-[0.94]',
                                    PRESSABLE,
                                )}
                            >
                                <Pencil className="size-4" aria-hidden="true" />
                            </button>
                        )}
                    </div>
                    {energyTotal > 0 && (
                        <div aria-hidden="true" className="flex h-3 gap-1">
                            {ENERGY_MACROS.map(
                                (macro, index) =>
                                    energy[macro.key] > 0 && (
                                        <span
                                            key={macro.key}
                                            style={{
                                                flexGrow:
                                                    energy[macro.key] /
                                                    energyTotal,
                                                animationDelay: `${index * 60 + 120}ms`,
                                            }}
                                            className={cn(
                                                'h-full min-w-3 basis-0 rounded-full fill-mode-both motion-safe:duration-300 motion-safe:animate-in motion-safe:fade-in',
                                                macro.bar,
                                            )}
                                        />
                                    ),
                            )}
                        </div>
                    )}
                    <ul className="grid grid-cols-4 gap-2">
                        {MACROS.map((macro) => {
                            const value = totals[macro.field];
                            const label = t(macro.labelKey);
                            const content = (
                                <>
                                    <span className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground sm:text-sm">
                                        <span
                                            aria-hidden="true"
                                            className={cn(
                                                'size-2 shrink-0 rounded-full',
                                                macro.bar,
                                            )}
                                        />
                                        <span className="truncate">
                                            {label}
                                        </span>
                                    </span>
                                    <span className="text-lg font-semibold tabular-nums sm:text-xl">
                                        {value === null
                                            ? '—'
                                            : t(
                                                  'health_entries.nutrition.grams',
                                                  {
                                                      value: formatAmount(
                                                          value,
                                                          locale,
                                                          1,
                                                      ),
                                                  },
                                              )}
                                    </span>
                                </>
                            );

                            return (
                                <li key={macro.key} className="min-w-0">
                                    {isItemized ? (
                                        <div className="flex flex-col gap-0.5">
                                            {content}
                                        </div>
                                    ) : (
                                        <button
                                            type="button"
                                            aria-label={t(
                                                'health_entries.detail.edit_value',
                                                { label },
                                            )}
                                            onClick={() =>
                                                openValue(macro.field)
                                            }
                                            className={cn(
                                                '-m-2 flex w-[calc(100%+1rem)] flex-col gap-0.5 rounded-2xl p-2 text-left transition-[background-color,scale] duration-150 ease-out hover:bg-muted/70 focus-visible:ring-2 focus-visible:ring-primary/50 active:scale-[0.97] active:bg-muted/70',
                                                errors[macro.field] &&
                                                    'ring-2 ring-destructive',
                                                PRESSABLE,
                                            )}
                                        >
                                            {content}
                                        </button>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </section>
                {isItemized && (
                    <section
                        aria-labelledby="ingredients-heading"
                        className="flex flex-col gap-3"
                    >
                        <div className="flex items-baseline justify-between gap-3">
                            <h2
                                id="ingredients-heading"
                                className="text-xl font-semibold tracking-tight"
                            >
                                {t('health_entries.detail.ingredients')}
                            </h2>
                            <p
                                id="ingredients-hint"
                                className="text-sm text-muted-foreground"
                            >
                                {t('health_entries.detail.ingredients_hint')}
                            </p>
                        </div>
                        <ul className="flex flex-col gap-0.5 rounded-[1.75rem] bg-muted/60 p-1.5">
                            {items.map((item, index) => {
                                const messages = Object.values(
                                    itemErrors(index),
                                );

                                return (
                                    <li key={item.key}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                openIngredient(index)
                                            }
                                            aria-describedby={
                                                messages.length > 0
                                                    ? `ingredients-hint ingredient-error-${index}`
                                                    : 'ingredients-hint'
                                            }
                                            className={cn(
                                                ROW,
                                                'min-h-16 py-3',
                                                messages.length > 0 &&
                                                    'bg-destructive/10',
                                                PRESSABLE,
                                            )}
                                        >
                                            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                                <span className="font-medium break-words">
                                                    {item.name}
                                                </span>
                                                {item.portion && (
                                                    <span className="truncate text-sm text-muted-foreground">
                                                        {item.portion}
                                                    </span>
                                                )}
                                            </span>
                                            <span className="shrink-0 text-right tabular-nums">
                                                <span className="font-semibold">
                                                    {formatAmount(
                                                        item.calories,
                                                        locale,
                                                    )}
                                                </span>{' '}
                                                <span className="text-sm text-muted-foreground">
                                                    {t(
                                                        'health_entries.nutrition.kcal',
                                                    )}
                                                </span>
                                            </span>
                                        </button>
                                        {messages.length > 0 && (
                                            <p
                                                id={`ingredient-error-${index}`}
                                                className="px-4 pt-1 pb-2 text-sm text-destructive"
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
                                        className={cn(
                                            ROW,
                                            'min-h-13 justify-center gap-2 font-semibold text-primary',
                                            PRESSABLE,
                                        )}
                                    >
                                        <Plus
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                        {t(
                                            'health_entries.detail.add_ingredient',
                                        )}
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
                    <MealTypeSwitch
                        value={data.meal_type}
                        onChange={(value) => form.setData('meal_type', value)}
                        labelledBy="meal-type-heading"
                    />
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
                        <p className="rounded-3xl bg-muted/60 p-5 text-sm break-words whitespace-pre-wrap">
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
                                        className="flex items-center gap-4 rounded-3xl bg-muted/60 p-4"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-background"
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
                                            className={cn(
                                                'size-11 shrink-0 rounded-full bg-transparent text-muted-foreground',
                                                PRESS_BUTTON,
                                            )}
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
                                    ? t(
                                          'snap_to_track.review.saved_confidence',
                                          { confidence: analysis.confidence },
                                      )
                                    : analysis
                                      ? t(
                                            'health_entries.detail.estimates_note',
                                        )
                                      : null,
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                    </p>
                )}
            </div>
            <DetailActions
                onEdit={() => openSheet('details')}
                doneUrl={backUrl}
                onDone={form.isDirty ? () => save() : undefined}
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
                        meal_name: title,
                        measured_at: data.measured_at,
                        notes: data.notes,
                    }}
                    automaticName={automaticTitle}
                    errors={errors}
                    saving={form.processing}
                    onApply={(details) => {
                        const mealName = details.meal_name.trim();
                        const isAutomatic =
                            mealName === mealTitle(items, details.notes, t);

                        form.setData((current) => ({
                            ...current,
                            ...details,
                            meal_name: isAutomatic ? '' : mealName,
                        }));
                        form.clearErrors('meal_name', 'measured_at', 'notes');
                        save(true);
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
