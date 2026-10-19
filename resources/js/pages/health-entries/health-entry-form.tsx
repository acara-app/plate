import StoreHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/StoreHealthEntryController';
import UpdateHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/UpdateHealthEntryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    formatLocalDatetime,
    localDatetimeToIso,
} from '@/lib/format-local-datetime';
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
    type?: LogTypeValue;
    defaultMeasuredAt?: string;
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
    type,
    defaultMeasuredAt: initialMeasuredAt,
    onCancel,
}: HealthEntryFormProps) {
    const { t } = useTranslation('common');
    const [activeType, setActiveType] = useState<LogTypeValue | null>(
        type ?? (mode === 'edit' && entry ? getPrimaryType(entry) : null),
    );

    const [defaultMeasuredAt] = useState(
        () =>
            initialMeasuredAt ??
            formatLocalDatetime(
                entry ? new Date(entry.measured_at) : new Date(),
            ),
    );

    if (activeType === null) {
        return (
            <div className="overflow-y-auto px-5 py-6 sm:px-6">
                <EntryTypePicker onSelect={setActiveType} />
            </div>
        );
    }

    const formAction =
        mode === 'edit' && entry
            ? UpdateHealthEntryController.form(entry.id)
            : StoreHealthEntryController.form();

    const typeConfig = getTypeConfig(activeType);
    const TypeIcon = typeConfig.icon;

    return (
        <Form
            {...formAction}
            transform={(data) => ({
                ...data,
                measured_at: localDatetimeToIso(String(data.measured_at ?? '')),
            })}
            disableWhileProcessing
            onSuccess={onCancel}
            className="flex min-h-0 flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <input type="hidden" name="log_type" value={activeType} />

                    <div className="flex min-h-0 flex-col gap-6 overflow-y-auto overscroll-contain px-5 py-6 sm:px-6">
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

                        <div className="flex flex-col gap-2">
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

                        <div className="flex flex-col gap-2">
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
                    </div>
                    <div className="flex shrink-0 flex-wrap justify-end gap-2 bg-muted/30 px-5 py-4 sm:px-6">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={onCancel}
                        >
                            {t('health_entries.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            variant="secondary"
                            className="h-11 min-w-24"
                            disabled={processing}
                        >
                            {mode === 'edit'
                                ? t('health_entries.common.update')
                                : t('health_entries.common.save')}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
