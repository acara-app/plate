import { Badge } from '@/components/ui/badge';
import { getEntryBadges } from '@/lib/health-entry-registry';
import { HealthEntry } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';

interface EntryValueBadgesProps {
    entry: HealthEntry;
    glucoseUnit: string;
}

export default function EntryValueBadges({
    entry,
    glucoseUnit,
}: EntryValueBadgesProps) {
    const { t } = useTranslation('common');
    const badges = getEntryBadges(entry, glucoseUnit, t);

    if (badges.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {badges.map((badge) => (
                <Badge
                    key={badge.key}
                    variant={badge.subtle ? 'outline' : 'default'}
                    className={
                        badge.subtle
                            ? 'text-muted-foreground capitalize'
                            : badge.className
                    }
                >
                    {badge.label}
                </Badge>
            ))}
        </div>
    );
}
