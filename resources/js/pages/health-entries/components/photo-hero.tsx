import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import MealPhotoImage from './meal-photo-image';

export default function PhotoHero({
    src,
    measuredAt,
}: {
    src: string;
    measuredAt: string;
}) {
    const { t, i18n } = useTranslation('common');

    return (
        <Dialog>
            <DialogTrigger asChild>
                <button
                    type="button"
                    aria-label={t('health_entries.timeline.view_photo')}
                    className="relative block aspect-[4/3] w-full overflow-hidden rounded-3xl bg-muted outline-none focus-visible:ring-[3px] focus-visible:ring-primary/50"
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
                className="flex max-h-[calc(100dvh-2rem)] flex-col overflow-hidden border-0 p-4 sm:max-w-3xl sm:p-6 [&>button]:flex [&>button]:size-11 [&>button]:items-center [&>button]:justify-center [&>button]:rounded-full [&>button]:bg-background [&>button]:opacity-100"
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
    );
}
