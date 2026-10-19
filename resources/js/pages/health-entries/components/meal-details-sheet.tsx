import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { bottomSheetClassName } from './ingredient-sheet';

type Details = { measured_at: string; notes: string };

function DetailsForm({
    details,
    errors,
    onApply,
}: {
    details: Details;
    errors: Record<string, string>;
    onApply: (details: Details) => void;
}) {
    const { t } = useTranslation('common');
    const [draft, setDraft] = useState(details);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onApply(draft);
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5 px-5 pb-6">
            <div className="flex flex-col gap-2">
                <Label htmlFor="meal-measured-at">
                    {t('health_entries.common.date_time_label')}
                </Label>
                <Input
                    id="meal-measured-at"
                    type="datetime-local"
                    required
                    value={draft.measured_at}
                    className="h-12 rounded-xl text-base"
                    onChange={(event) =>
                        setDraft({ ...draft, measured_at: event.target.value })
                    }
                />
                <InputError message={errors.measured_at} />
            </div>
            <div className="flex flex-col gap-2">
                <Label htmlFor="meal-notes">
                    {t('health_entries.common.notes_label')}
                </Label>
                <Textarea
                    id="meal-notes"
                    value={draft.notes}
                    maxLength={500}
                    placeholder={t('health_entries.common.notes_placeholder')}
                    className="min-h-24 rounded-xl text-base"
                    onChange={(event) =>
                        setDraft({ ...draft, notes: event.target.value })
                    }
                />
                <InputError message={errors.notes} />
            </div>
            <Button type="submit" className="h-12 rounded-2xl text-base">
                {t('health_entries.detail.apply')}
            </Button>
        </form>
    );
}

export default function MealDetailsSheet({
    open,
    onOpenChange,
    formKey,
    details,
    errors,
    onApply,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    formKey: number;
    details: Details;
    errors: Record<string, string>;
    onApply: (details: Details) => void;
}) {
    const { t } = useTranslation('common');

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                closeLabel={t('health_entries.timeline.close')}
                className={bottomSheetClassName}
            >
                <SheetHeader className="px-5 pt-6 pr-16 pb-4">
                    <SheetTitle className="text-xl">
                        {t('health_entries.detail.details_title')}
                    </SheetTitle>
                    <SheetDescription>
                        {t('health_entries.detail.details_description')}
                    </SheetDescription>
                </SheetHeader>
                <DetailsForm
                    key={formKey}
                    details={details}
                    errors={errors}
                    onApply={onApply}
                />
            </SheetContent>
        </Sheet>
    );
}
