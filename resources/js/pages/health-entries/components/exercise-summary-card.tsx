import { getTypeConfig } from '@/lib/health-entry-registry';
import { cn } from '@/lib/utils';
import { LogType } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import { ENTRY_CARD } from './entry-card-layout';
import { formatAmount } from './macros';

export default function ExerciseSummaryCard({ minutes }: { minutes: number }) {
    const { t, i18n } = useTranslation('common');
    const typeConfig = getTypeConfig(LogType.Exercise);
    const caption = t('health_entries.logbook.synced_exercise_total');

    return (
        <div role="group" aria-label={caption} className={ENTRY_CARD.shell}>
            <span
                aria-hidden="true"
                className={cn(
                    ENTRY_CARD.media,
                    'flex items-center justify-center bg-muted',
                )}
            >
                <typeConfig.icon className={cn('size-8', typeConfig.accent)} />
            </span>
            <div className={ENTRY_CARD.body}>
                <h3 className="leading-snug font-semibold">
                    {t(typeConfig.labelKey)}
                </h3>
                <p className={ENTRY_CARD.value}>
                    {t('health_entries.badges.exercise_minutes', {
                        value: formatAmount(minutes, i18n.resolvedLanguage),
                    })}
                </p>
                <p className="text-sm text-muted-foreground">{caption}</p>
            </div>
        </div>
    );
}
