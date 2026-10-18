import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import type { HealthEntry } from '@/types/diabetes';
import { ImageOff, Utensils } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import MealNutrition from './meal-nutrition';

function PhotoImage({ src, full = false }: { src: string; full?: boolean }) {
    const { t } = useTranslation('common');
    const [status, setStatus] = useState<'loading' | 'loaded' | 'failed'>(
        'loading',
    );

    if (status === 'failed') {
        return (
            <div
                role="status"
                className="flex h-full min-h-20 flex-col items-center justify-center gap-2 p-3 text-center text-xs text-muted-foreground"
            >
                <ImageOff className="size-5" />
                <span>{t('health_entries.timeline.photo_unavailable')}</span>
            </div>
        );
    }

    return (
        <>
            {status === 'loading' && (
                <Skeleton className="absolute inset-0 rounded-none" />
            )}
            <img
                src={src}
                alt={t('health_entries.meal_photo_alt')}
                loading={full ? 'eager' : 'lazy'}
                onLoad={() => setStatus('loaded')}
                onError={() => setStatus('failed')}
                className={
                    full
                        ? 'relative max-h-[60dvh] w-full object-contain'
                        : 'relative size-full object-cover'
                }
            />
        </>
    );
}

export default function MealPhoto({ entry }: { entry: HealthEntry }) {
    const { t, i18n } = useTranslation('common');

    if (!entry.photo_url) {
        return (
            <div
                className="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-muted/50 text-muted-foreground sm:size-24"
                aria-hidden="true"
            >
                <Utensils className="size-6" />
            </div>
        );
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    className="relative size-20 shrink-0 overflow-hidden rounded-2xl p-0 sm:size-24"
                    aria-label={t('health_entries.timeline.view_photo')}
                >
                    <PhotoImage src={entry.photo_url} />
                </Button>
            </DialogTrigger>
            <DialogContent
                closeLabel={t('health_entries.timeline.close')}
                className="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden border-0 p-4 sm:max-w-3xl sm:p-6 [&>button]:flex [&>button]:size-11 [&>button]:items-center [&>button]:justify-center [&>button]:rounded-full [&>button]:bg-background [&>button]:opacity-100"
            >
                <DialogHeader className="shrink-0 pr-12 text-left">
                    <DialogTitle>
                        {t('health_entries.meal_photo_alt')}
                    </DialogTitle>
                    <DialogDescription>
                        {new Date(entry.measured_at).toLocaleString(
                            i18n.resolvedLanguage,
                            { dateStyle: 'medium', timeStyle: 'short' },
                        )}
                    </DialogDescription>
                </DialogHeader>
                <div className="flex min-h-0 flex-col gap-4 overflow-y-auto overscroll-contain">
                    <div className="relative flex min-h-40 items-center justify-center overflow-hidden rounded-xl bg-muted/40">
                        <PhotoImage
                            key={entry.photo_url}
                            src={entry.photo_url}
                            full
                        />
                    </div>
                    <MealNutrition entry={entry} />
                </div>
            </DialogContent>
        </Dialog>
    );
}
