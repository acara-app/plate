import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    getEntryBadges,
    getPrimaryType,
    getTypeConfig,
    SOURCE_META,
} from '@/lib/health-entry-registry';
import type { HealthEntry } from '@/types/diabetes';
import { ChevronDown, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import FoodBreakdownDetails from './food-breakdown';
import MealNutrition from './meal-nutrition';
import MealPhoto from './meal-photo';

export default function EntryRow({
    entry,
    glucoseUnit,
    onEdit,
    onDelete,
}: {
    entry: HealthEntry;
    glucoseUnit: string;
    onEdit: () => void;
    onDelete: () => void;
}) {
    const { t, i18n } = useTranslation('common');
    const type = getPrimaryType(entry);
    const config = getTypeConfig(type);
    const Icon = config.icon;
    const source = entry.source ? SOURCE_META[entry.source] : undefined;
    const isFood =
        type === 'food' ||
        entry.food_analysis !== null ||
        entry.photo_url !== null;
    const readings = getEntryBadges(entry, glucoseUnit, t).filter(
        (value) =>
            !isFood ||
            !['calories', 'protein', 'carbs', 'fat'].includes(value.key),
    );
    const items = entry.food_analysis?.items ?? [];

    return (
        <article
            className="flex min-w-0 flex-col gap-3 py-4 sm:py-5"
            aria-label={t(config.labelKey)}
        >
            <div className="flex min-w-0 items-start gap-4">
                {isFood ? (
                    <MealPhoto entry={entry} />
                ) : (
                    <div
                        className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted/50 text-muted-foreground"
                        aria-hidden="true"
                    >
                        <Icon className="size-5" />
                    </div>
                )}
                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex min-w-0 items-start justify-between gap-2">
                        <div className="flex min-w-0 flex-col gap-1">
                            <h3 className="font-medium">
                                {t(
                                    isFood
                                        ? 'health_entries.timeline.meal'
                                        : config.labelKey,
                                )}
                            </h3>
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                <time dateTime={entry.measured_at}>
                                    {new Date(
                                        entry.measured_at,
                                    ).toLocaleTimeString(
                                        i18n.resolvedLanguage,
                                        { hour: 'numeric', minute: '2-digit' },
                                    )}
                                </time>
                                {(entry.food_analysis || source) && (
                                    <>
                                        <span aria-hidden="true">·</span>
                                        <span>
                                            {entry.food_analysis
                                                ? t('snap_to_track.title')
                                                : source && t(source.labelKey)}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-11"
                                onClick={onEdit}
                                aria-label={t(
                                    'health_entries.index_page.edit_aria_label',
                                )}
                            >
                                <Pencil aria-hidden="true" />
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-11"
                                        aria-label={t(
                                            'health_entries.timeline.more_actions',
                                        )}
                                    >
                                        <MoreHorizontal aria-hidden="true" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuGroup>
                                        <DropdownMenuItem
                                            variant="destructive"
                                            onSelect={onDelete}
                                        >
                                            <Trash2 aria-hidden="true" />
                                            {t(
                                                'health_entries.timeline.delete',
                                            )}
                                        </DropdownMenuItem>
                                    </DropdownMenuGroup>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                    {items.length > 0 && (
                        <div className="flex min-w-0 items-baseline gap-2 text-sm text-muted-foreground">
                            <p className="line-clamp-2 min-w-0 flex-1">
                                {items
                                    .slice(0, 2)
                                    .map((item) => item.name)
                                    .join(', ')}
                            </p>
                            {items.length > 2 && (
                                <span className="shrink-0 text-xs">
                                    {t('health_entries.timeline.more_items', {
                                        count: items.length - 2,
                                    })}
                                </span>
                            )}
                        </div>
                    )}
                    {readings.length > 0 && (
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm font-medium break-words tabular-nums">
                            {readings.map((value) => (
                                <span key={value.key}>{value.label}</span>
                            ))}
                        </div>
                    )}
                    {isFood && (
                        <div className="hidden sm:block">
                            <MealNutrition entry={entry} />
                        </div>
                    )}
                </div>
            </div>
            {isFood && (
                <div className="sm:hidden">
                    <MealNutrition entry={entry} />
                </div>
            )}
            {entry.notes && (
                <p className="text-sm break-words whitespace-pre-wrap text-muted-foreground">
                    {entry.notes}
                </p>
            )}
            {entry.food_analysis && (
                <Collapsible>
                    <CollapsibleTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="group -ml-2 h-10 gap-2"
                            aria-label={t(
                                'health_entries.timeline.meal_details',
                            )}
                        >
                            <span>
                                {t('health_entries.timeline.meal_details')}
                            </span>
                            <ChevronDown
                                className="transition-transform group-data-[state=open]:rotate-180"
                                aria-hidden="true"
                            />
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="pt-3">
                        <FoodBreakdownDetails analysis={entry.food_analysis} />
                    </CollapsibleContent>
                </Collapsible>
            )}
        </article>
    );
}
