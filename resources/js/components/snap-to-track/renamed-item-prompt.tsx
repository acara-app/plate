import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

export function RenamedItemPrompt({
    originalName,
    name,
    estimating,
    disabled,
    failed,
    onUpdate,
}: {
    originalName: string;
    name: string;
    estimating: boolean;
    disabled: boolean;
    failed: boolean;
    onUpdate: () => void;
}) {
    const { t } = useTranslation('common');

    return (
        <div className="flex flex-col gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20">
            <p className="text-amber-800 dark:text-amber-200">
                {t('snap_to_track.review.rename.still_for', {
                    name: originalName,
                })}
            </p>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={disabled}
                    onClick={onUpdate}
                >
                    {estimating
                        ? t('snap_to_track.review.rename.updating')
                        : t('snap_to_track.review.rename.update', {
                              name: name.trim(),
                          })}
                </Button>
            </div>
            {failed && (
                <p className="text-xs text-amber-800 dark:text-amber-200">
                    {t('snap_to_track.review.rename.failed')}
                </p>
            )}
        </div>
    );
}
