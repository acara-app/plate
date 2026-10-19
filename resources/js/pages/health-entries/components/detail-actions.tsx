import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

export default function DetailActions({
    onEdit,
    doneUrl,
    onDone,
    saving = false,
    message,
}: {
    onEdit: () => void;
    doneUrl: string;
    onDone?: () => void;
    saving?: boolean;
    message?: ReactNode;
}) {
    const { t } = useTranslation('common');
    const doneClassName = 'h-12 rounded-2xl text-base';

    return (
        <div className="fixed inset-x-0 bottom-0 z-20 border-t bg-background/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:z-auto md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none">
            {message && <div className="mx-auto mb-3 max-w-xl">{message}</div>}
            <div className="mx-auto grid max-w-xl grid-cols-2 gap-3">
                <Button
                    type="button"
                    variant="outline"
                    className="h-12 rounded-2xl text-base"
                    onClick={onEdit}
                    disabled={saving}
                >
                    {t('health_entries.detail.edit')}
                </Button>
                {onDone ? (
                    <Button
                        type="button"
                        className={doneClassName}
                        onClick={onDone}
                        disabled={saving}
                    >
                        {t(
                            saving
                                ? 'health_entries.detail.saving'
                                : 'health_entries.detail.done',
                        )}
                    </Button>
                ) : (
                    <Button asChild className={doneClassName}>
                        <Link href={doneUrl}>
                            {t('health_entries.detail.done')}
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
