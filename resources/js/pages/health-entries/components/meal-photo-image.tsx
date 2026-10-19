import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { ImageOff } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function MealPhotoImage({
    src,
    fit = 'cover',
    eager = false,
    className,
}: {
    src: string;
    fit?: 'cover' | 'contain';
    eager?: boolean;
    className?: string;
}) {
    const { t } = useTranslation('common');
    const [status, setStatus] = useState<'loading' | 'loaded' | 'failed'>(
        'loading',
    );

    if (status === 'failed') {
        return (
            <span className="flex size-full min-h-20 flex-col items-center justify-center gap-2 bg-muted p-3 text-center text-xs text-muted-foreground">
                <ImageOff className="size-5" aria-hidden="true" />
                <span>{t('health_entries.timeline.photo_unavailable')}</span>
            </span>
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
                loading={eager ? 'eager' : 'lazy'}
                onLoad={() => setStatus('loaded')}
                onError={() => setStatus('failed')}
                className={cn(
                    'relative',
                    fit === 'cover'
                        ? 'size-full object-cover'
                        : 'h-auto w-full object-contain',
                    className,
                )}
            />
        </>
    );
}
