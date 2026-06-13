import StoreHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/StoreHealthEntryController';
import UpdateHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/UpdateHealthEntryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatLocalDatetime } from '@/lib/format-local-datetime';
import { getPrimaryType, getTypeConfig } from '@/lib/health-entry-registry';
import { cn } from '@/lib/utils';
import {
    HealthEntry,
    LogTypeValue,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Form } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import EntryFields from './components/entry-fields';
import EntryTypePicker from './components/entry-type-picker';

interface HealthEntryFormProps {
    mode: 'create' | 'edit';
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
    entry?: HealthEntry;
    onCancel: () => void;
}

export default function HealthEntryForm({
    mode,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications = [],
    recentInsulins = [],
    todaysMeals = [],
    entry,
    onCancel,
}: HealthEntryFormProps) {
    const { t } = useTranslation('common');
    const [activeType, setActiveType] = useState<LogTypeValue | null>(
        mode === 'edit' && entry ? getPrimaryType(entry) : null,
    );

    if (activeType === null) {
        return <EntryTypePicker onSelect={setActiveType} />;
    }

    const formAction =
        mode === 'edit' && entry
            ? UpdateHealthEntryController.form(entry.id)
            : StoreHealthEntryController.form();

    const typeConfig = getTypeConfig(activeType);
    const TypeIcon = typeConfig.icon;
    const defaultMeasuredAt = formatLocalDatetime(
        entry ? new Date(entry.measured_at) : new Date(),
    );

    return (
        <Form
            {...formAction}
            disableWhileProcessing
            onSuccess={onCancel}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <input type="hidden" name="log_type" value={activeType} />

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <TypeIcon
                                className={cn('size-5', typeConfig.accent)}
                                aria-hidden="true"
                            />
                            <span className="font-medium">
                                {t(typeConfig.labelKey)}
                            </span>
                        </div>
                        {mode === 'create' && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setActiveType(null)}
                            >
                                <ChevronLeft
                                    className="mr-1 size-4"
                                    aria-hidden="true"
                                />
                                {t('health_entries.picker.back')}
                            </Button>
                        )}
                    </div>

                    <EntryFields
                        type={activeType}
                        entry={entry}
                        errors={errors}
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        glucoseUnit={glucoseUnit}
                        recentMedications={recentMedications}
                        recentInsulins={recentInsulins}
                        todaysMeals={todaysMeals}
                    />

                    <div className="space-y-2">
                        <Label htmlFor="notes">
                            {t('health_entries.common.notes_label')}
                        </Label>
                        <Textarea
                            id="notes"
                            name="notes"
                            placeholder={t(
                                'health_entries.common.notes_placeholder',
                            )}
                            defaultValue={entry?.notes ?? ''}
                            maxLength={500}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="space-y-2 sm:flex-1">
                            <Label htmlFor="measured_at">
                                {t('health_entries.common.date_time_label')}
                            </Label>
                            <Input
                                id="measured_at"
                                type="datetime-local"
                                name="measured_at"
                                defaultValue={defaultMeasuredAt}
                                required
                            />
                            <InputError message={errors.measured_at} />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onCancel}
                            >
                                {t('health_entries.common.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {mode === 'edit'
                                    ? t('health_entries.common.update')
                                    : t('health_entries.common.create')}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
