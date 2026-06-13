import { Badge } from '@/components/ui/badge';
import { SOURCE_META } from '@/lib/health-entry-registry';
import { useTranslation } from 'react-i18next';

interface SourceBadgeProps {
    source: string | null;
}

export default function SourceBadge({ source }: SourceBadgeProps) {
    const { t } = useTranslation('common');
    const meta = source ? SOURCE_META[source] : undefined;

    if (!meta) {
        return null;
    }

    const Icon = meta.icon;
    const label = t(meta.labelKey);

    return (
        <Badge
            variant="outline"
            className="gap-1 text-muted-foreground"
            aria-label={label}
        >
            <Icon className="size-3" aria-hidden="true" />
            {label}
        </Badge>
    );
}
