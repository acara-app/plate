import { HEALTH_ENTRY_TYPES } from '@/lib/health-entry-registry';
import { cn } from '@/lib/utils';
import { LogTypeValue } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';

interface EntryTypePickerProps {
    onSelect: (type: LogTypeValue) => void;
}

export default function EntryTypePicker({ onSelect }: EntryTypePickerProps) {
    const { t } = useTranslation('common');

    return (
        <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
                {t('health_entries.picker.heading')}
            </p>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {HEALTH_ENTRY_TYPES.map(
                    ({ key, icon: Icon, labelKey, accent }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => onSelect(key)}
                            className="flex flex-col items-center justify-center gap-2 rounded-lg border bg-card p-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Icon
                                className={cn('size-6', accent)}
                                aria-hidden="true"
                            />
                            {t(labelKey)}
                        </button>
                    ),
                )}
            </div>
        </div>
    );
}
