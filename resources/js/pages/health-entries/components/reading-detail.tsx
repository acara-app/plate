import { Button } from '@/components/ui/button';
import {
    getPrimaryType,
    getReadingGroups,
    getTypeConfig,
    SOURCE_META,
} from '@/lib/health-entry-registry';
import { cn } from '@/lib/utils';
import type { HealthEntry, LogTypeValue } from '@/types/diabetes';
import { Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import DetailActions from './detail-actions';
import DetailTopBar from './detail-top-bar';

export default function ReadingDetail({
    entry,
    backUrl,
    glucoseUnit,
    onEdit,
}: {
    entry: HealthEntry;
    backUrl: string;
    glucoseUnit: string;
    onEdit: (type: LogTypeValue) => void;
}) {
    const { t, i18n } = useTranslation('common');
    const primaryType = getPrimaryType(entry);
    const config = getTypeConfig(primaryType);
    const groups = getReadingGroups(entry, glucoseUnit, t);
    const source = entry.source ? SOURCE_META[entry.source] : undefined;

    return (
        <div className="mx-auto flex w-full max-w-xl min-w-0 flex-col gap-6 px-4 pt-3 pb-36 sm:px-6 md:pb-10">
            <DetailTopBar entry={entry} backUrl={backUrl} isDirty={false} />
            <h1 className="-mt-3 text-4xl font-bold tracking-tight">
                {t(config.labelKey)}
            </h1>
            <div className="flex flex-col gap-5 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60 sm:p-6">
                <div className="flex items-center gap-4">
                    <span
                        aria-hidden="true"
                        className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-muted"
                    >
                        <config.icon className={cn('size-7', config.accent)} />
                    </span>
                    <div className="flex min-w-0 flex-col text-sm text-muted-foreground">
                        <time dateTime={entry.measured_at}>
                            {new Date(entry.measured_at).toLocaleString(
                                i18n.resolvedLanguage,
                                { dateStyle: 'medium', timeStyle: 'short' },
                            )}
                        </time>
                        {source && <span>{t(source.labelKey)}</span>}
                    </div>
                </div>
                <dl className="flex flex-col gap-4">
                    {groups.map((group) => (
                        <div
                            key={group.type}
                            className="flex items-center gap-3"
                        >
                            <div className="flex min-w-0 flex-1 flex-col-reverse gap-1">
                                <dt className="text-sm text-muted-foreground">
                                    {t(getTypeConfig(group.type).labelKey)}
                                </dt>
                                <dd className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    {group.badges.map((badge) => (
                                        <span
                                            key={badge.key}
                                            className={
                                                badge.subtle
                                                    ? 'text-sm text-muted-foreground first-letter:uppercase'
                                                    : 'text-3xl font-bold tracking-tight break-words tabular-nums'
                                            }
                                        >
                                            {badge.label}
                                        </span>
                                    ))}
                                </dd>
                            </div>
                            {groups.length > 1 && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 shrink-0 rounded-full text-muted-foreground"
                                    aria-label={t(
                                        'health_entries.detail.edit_value',
                                        {
                                            label: t(
                                                getTypeConfig(group.type)
                                                    .labelKey,
                                            ),
                                        },
                                    )}
                                    onClick={() => onEdit(group.type)}
                                >
                                    <Pencil aria-hidden="true" />
                                </Button>
                            )}
                        </div>
                    ))}
                </dl>
            </div>
            {entry.notes && (
                <section
                    aria-labelledby="reading-notes-heading"
                    className="flex flex-col gap-3"
                >
                    <h2
                        id="reading-notes-heading"
                        className="text-xl font-semibold tracking-tight"
                    >
                        {t('health_entries.detail.notes')}
                    </h2>
                    <p className="rounded-3xl bg-card p-5 text-sm break-words whitespace-pre-wrap shadow-xs ring-1 ring-border/60">
                        {entry.notes}
                    </p>
                </section>
            )}
            <DetailActions
                onEdit={() => onEdit(primaryType)}
                doneUrl={backUrl}
            />
        </div>
    );
}
