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
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { bottomSheetClassName } from './ingredient-sheet';

function ValueForm({
    label,
    unit,
    value,
    integer,
    error,
    onApply,
}: {
    label: string;
    unit: string;
    value: number | null;
    integer: boolean;
    error?: string;
    onApply: (value: number | null) => void;
}) {
    const { t } = useTranslation('common');
    const [draft, setDraft] = useState(value === null ? '' : String(value));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const number = Number(draft);

        onApply(
            draft.trim() === '' || !Number.isFinite(number)
                ? null
                : Math.max(0, integer ? Math.round(number) : number),
        );
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5 px-5 pb-6">
            <div className="flex flex-col gap-2">
                <Label htmlFor="nutrition-value">
                    {label} ({unit})
                </Label>
                <Input
                    id="nutrition-value"
                    type="number"
                    inputMode={integer ? 'numeric' : 'decimal'}
                    min={0}
                    step={integer ? '1' : '0.1'}
                    value={draft}
                    autoFocus
                    aria-invalid={Boolean(error)}
                    className="h-14 rounded-xl text-2xl font-semibold tabular-nums"
                    onChange={(event) => setDraft(event.target.value)}
                />
                <InputError message={error} />
            </div>
            <Button type="submit" className="h-12 rounded-2xl text-base">
                {t('health_entries.detail.apply')}
            </Button>
        </form>
    );
}

export default function ValueSheet({
    open,
    onOpenChange,
    formKey,
    label,
    unit,
    value,
    integer = false,
    error,
    onApply,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    formKey: number;
    label: string;
    unit: string;
    value: number | null;
    integer?: boolean;
    error?: string;
    onApply: (value: number | null) => void;
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
                        {t('health_entries.detail.edit_value', { label })}
                    </SheetTitle>
                    <SheetDescription>
                        {t('health_entries.detail.value_description')}
                    </SheetDescription>
                </SheetHeader>
                <ValueForm
                    key={formKey}
                    label={label}
                    unit={unit}
                    value={value}
                    integer={integer}
                    error={error}
                    onApply={onApply}
                />
            </SheetContent>
        </Sheet>
    );
}
