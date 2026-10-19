import DestroyHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/DestroyHealthEntryController';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { HealthEntry } from '@/types/diabetes';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function DetailTopBar({
    entry,
    backUrl,
}: {
    entry: HealthEntry;
    backUrl: string;
}) {
    const { t } = useTranslation('common');
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const removeEntry = () => {
        router.delete(DestroyHealthEntryController(entry.id).url, {
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <div className="flex items-center justify-between">
            <Button
                asChild
                variant="ghost"
                size="icon"
                className="-ml-2 size-11 rounded-full"
            >
                <Link
                    href={backUrl}
                    aria-label={t('health_entries.detail.back')}
                >
                    <ChevronLeft className="size-6" aria-hidden="true" />
                </Link>
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="-mr-2 size-11 rounded-full"
                        aria-label={t('health_entries.timeline.more_actions')}
                    >
                        <MoreHorizontal className="size-6" aria-hidden="true" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setConfirmingDelete(true)}
                    >
                        <Trash2 aria-hidden="true" />
                        {t('health_entries.timeline.delete')}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <AlertDialog
                open={confirmingDelete}
                onOpenChange={(open) => !deleting && setConfirmingDelete(open)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('health_entries.timeline.delete_title')}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {t(
                                entry.photo_url
                                    ? 'health_entries.timeline.delete_photo_description'
                                    : 'health_entries.timeline.delete_description',
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={deleting}>
                            {t('health_entries.common.cancel')}
                        </AlertDialogCancel>
                        <Button
                            variant="destructive"
                            disabled={deleting}
                            onClick={removeEntry}
                        >
                            {t(
                                deleting
                                    ? 'health_entries.timeline.deleting'
                                    : 'health_entries.timeline.delete',
                            )}
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
