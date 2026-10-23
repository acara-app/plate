import ShowHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ShowHealthEntryController';
import {
    entryTitle,
    getEntryBadges,
    getPrimaryType,
    getTypeConfig,
    hasFood,
} from '@/lib/health-entry-registry';
import { getMealTypeConfig } from '@/lib/meal-types';
import { cn } from '@/lib/utils';
import type { HealthEntry } from '@/types/diabetes';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ENTRY_CARD } from './entry-card-layout';
import { formatAmount, MACROS } from './macros';
import MealPhotoImage from './meal-photo-image';

export default function EntryCard({
    entry,
    glucoseUnit,
}: {
    entry: HealthEntry;
    glucoseUnit: string;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const isMeal = hasFood(entry);
    const typeConfig = getTypeConfig(getPrimaryType(entry));
    const mealType =
        isMeal && entry.meal_type ? getMealTypeConfig(entry.meal_type) : null;
    const PlaceholderIcon = mealType?.Icon ?? typeConfig.icon;
    const readings = getEntryBadges(entry, glucoseUnit, t);

    return (
        <Link
            href={ShowHealthEntryController(entry.id)}
            className={cn(
                ENTRY_CARD.shell,
                'group transition outline-none hover:ring-primary/40 focus-visible:ring-[3px] focus-visible:ring-primary/50',
            )}
        >
            <div className={cn(ENTRY_CARD.media, 'relative overflow-hidden')}>
                {entry.photo_url ? (
                    <MealPhotoImage
                        src={entry.photo_url}
                        className="absolute inset-0"
                    />
                ) : (
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-full items-center justify-center',
                            mealType ? mealType.color : 'bg-muted',
                        )}
                    >
                        <PlaceholderIcon
                            className={cn(
                                'size-8',
                                mealType
                                    ? mealType.iconColor
                                    : typeConfig.accent,
                            )}
                        />
                    </span>
                )}
            </div>
            <div className={ENTRY_CARD.body}>
                <div className="flex min-w-0 items-baseline gap-2">
                    <h3 className="line-clamp-2 min-w-0 flex-1 leading-snug font-semibold break-words">
                        {entryTitle(entry, t)}
                    </h3>
                    <time
                        dateTime={entry.measured_at}
                        className="shrink-0 text-xs text-muted-foreground tabular-nums"
                    >
                        {new Date(entry.measured_at).toLocaleTimeString(
                            locale,
                            { hour: 'numeric', minute: '2-digit' },
                        )}
                    </time>
                </div>
                {isMeal && entry.calories !== null && (
                    <p className={ENTRY_CARD.value}>
                        {formatAmount(entry.calories, locale)}{' '}
                        {t('health_entries.nutrition.kcal')}
                    </p>
                )}
                {isMeal && (
                    <ul className="flex flex-wrap gap-x-3 gap-y-1.5">
                        {MACROS.map(
                            (macro) =>
                                entry[macro.field] !== null && (
                                    <li
                                        key={macro.key}
                                        className="flex items-center gap-1.5"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={cn(
                                                'flex size-7 items-center justify-center rounded-lg',
                                                macro.tile,
                                            )}
                                        >
                                            <macro.Icon
                                                className={cn(
                                                    'size-4',
                                                    macro.icon,
                                                )}
                                            />
                                        </span>
                                        <span className="text-sm text-muted-foreground tabular-nums">
                                            <span className="sr-only">
                                                {t(macro.labelKey)}{' '}
                                            </span>
                                            {t(
                                                'health_entries.nutrition.grams',
                                                {
                                                    value: formatAmount(
                                                        entry[macro.field] ?? 0,
                                                        locale,
                                                    ),
                                                },
                                            )}
                                        </span>
                                    </li>
                                ),
                        )}
                    </ul>
                )}
                {readings.length > 0 && (
                    <p
                        className={cn(
                            'text-sm break-words',
                            isMeal
                                ? 'text-muted-foreground'
                                : 'font-semibold text-primary',
                        )}
                    >
                        {readings.map((badge) => badge.label).join(' · ')}
                    </p>
                )}
            </div>
        </Link>
    );
}
