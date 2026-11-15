import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import MealPhotoImage from './meal-photo-image';
import { PRESSABLE } from './pressable';

export default function PhotoHero({
    src,
    measuredAt,
    className,
    children,
}: {
    src: string;
    measuredAt: string;
    className?: string;
    children?: ReactNode;
}) {
    const { t, i18n } = useTranslation('common');

    return (
        <div
            className={cn(
                'relative aspect-square w-full shrink-0 overflow-hidden bg-muted',
                className,
            )}
        >
            <Dialog>
                <DialogTrigger asChild>
                    <button
                        type="button"
                        aria-label={t('health_entries.timeline.view_photo')}
                        className={cn(
                            'absolute inset-0 size-full transition-opacity duration-150 ease-out focus-visible:ring-[3px] focus-visible:ring-white/80 focus-visible:ring-inset active:opacity-90',
                            PRESSABLE,
                        )}
                    >
                        <MealPhotoImage
                            src={src}
                            eager
                            className="absolute inset-0"
                        />
                    </button>
                </DialogTrigger>
                <DialogContent
                    closeLabel={t('health_entries.timeline.close')}
                    className="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden border-0 p-4 sm:max-w-3xl sm:p-6 [&>button]:flex [&>button]:size-11 [&>button]:touch-manipulation [&>button]:items-center [&>button]:justify-center [&>button]:rounded-full [&>button]:bg-background [&>button]:opacity-100"
                >
                    <DialogHeader className="shrink-0 pr-12 text-left">
                        <DialogTitle>
                            {t('health_entries.meal_photo_alt')}
                        </DialogTitle>
                        <DialogDescription>
                            {new Date(measuredAt).toLocaleString(
                                i18n.resolvedLanguage,
                                { dateStyle: 'medium', timeStyle: 'short' },
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="relative flex min-h-40 items-center justify-center overflow-hidden rounded-xl bg-muted/40">
                        <MealPhotoImage
                            key={src}
                            src={src}
                            fit="contain"
                            eager
                            className="max-h-[70dvh]"
                        />
                    </div>
                </DialogContent>
            </Dialog>
            {children}
        </div>
    );
}
