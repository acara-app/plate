import PasskeyFeedback from '@/components/passkey-feedback';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { confirmPasskeyManagement } from '@/lib/confirm-passkey-management';
import { destroy } from '@/routes/passkey';
import { type Passkey } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { KeyRound, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function PasskeyItem({ passkey }: { passkey: Passkey }) {
    const { t } = useTranslation('auth');
    const { locale } = usePage().props;
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<Error | null>(null);
    const [open, setOpen] = useState(false);
    const formatDate = (date: string) =>
        new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(
            new Date(date),
        );

    const handleDelete = async () => {
        setBusy(true);
        setError(null);
        try {
            if (!(await confirmPasskeyManagement())) {
                setBusy(false);
                return;
            }
            router.delete(destroy.url(passkey.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
                onError: () => setError(new Error('Removal failed.')),
                onHttpException: () => {
                    setError(new Error('Removal failed.'));
                    return false;
                },
                onNetworkError: () => {
                    setError(new Error('Removal failed.'));
                    return false;
                },
                onFinish: () => setBusy(false),
            });
        } catch {
            setError(new Error('Removal failed.'));
            setBusy(false);
        }
    };

    return (
        <li className="flex items-center justify-between gap-4 p-4">
            <div className="flex min-w-0 items-center gap-3">
                <KeyRound className="size-5 shrink-0 text-muted-foreground" />
                <div className="min-w-0 space-y-1">
                    <p className="font-medium wrap-break-word">
                        {passkey.name}
                    </p>
                    {passkey.authenticator && (
                        <p className="text-sm text-muted-foreground">
                            {passkey.authenticator}
                        </p>
                    )}
                    {passkey.created_at && (
                        <p className="text-xs text-muted-foreground">
                            {t('passkeys.created', {
                                date: formatDate(passkey.created_at),
                            })}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        {passkey.last_used_at
                            ? t('passkeys.last_used', {
                                  date: formatDate(passkey.last_used_at),
                              })
                            : t('passkeys.never_used')}
                    </p>
                </div>
            </div>
            <Dialog
                open={open}
                onOpenChange={(value) => {
                    if (!busy) {
                        setOpen(value);
                        setError(null);
                    }
                }}
            >
                <DialogTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={t('passkeys.remove_named', {
                            name: passkey.name,
                        })}
                    >
                        <Trash2 className="size-4 text-destructive" />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>{t('passkeys.remove')}</DialogTitle>
                    <DialogDescription>
                        {t('passkeys.remove_description', {
                            name: passkey.name,
                        })}
                    </DialogDescription>
                    <PasskeyFeedback error={error} />
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary" disabled={busy}>
                                {t('passkeys.cancel')}
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={busy}
                        >
                            {t(busy ? 'passkeys.removing' : 'passkeys.remove')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </li>
    );
}
