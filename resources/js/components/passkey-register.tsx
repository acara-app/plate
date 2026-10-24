import PasskeyFeedback from '@/components/passkey-feedback';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { confirmPasskeyManagement } from '@/lib/confirm-passkey-management';
import { registrationOptions, store } from '@/routes/passkey';
import { edit } from '@/routes/passkeys';
import { router } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function PasskeyRegister() {
    const { t } = useTranslation('auth');
    const [name, setName] = useState('');
    const [showForm, setShowForm] = useState(false);
    const [checking, setChecking] = useState(false);
    const [confirmationError, setConfirmationError] = useState<Error | null>(
        null,
    );
    const { register, isLoading, errorInstance, isSupported } =
        usePasskeyRegister({
            routes: { options: registrationOptions.url(), submit: store.url() },
            onSuccess: () => {
                setName('');
                setShowForm(false);
                router.reload({ only: ['passkeys'] });
            },
        });
    const busy = checking || isLoading;

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        if (!name.trim() || busy) return;
        setConfirmationError(null);
        setChecking(true);
        try {
            if (await confirmPasskeyManagement()) await register(name.trim());
        } catch {
            setConfirmationError(new Error('Confirmation failed.'));
        } finally {
            setChecking(false);
        }
    };

    if (!isSupported) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('passkeys.unsupported')}
            </p>
        );
    }

    if (!showForm) {
        return (
            <Button variant="outline" onClick={() => setShowForm(true)}>
                <Plus />
                {t('passkeys.add')}
            </Button>
        );
    }

    return (
        <form
            onSubmit={handleSubmit}
            className="space-y-4 rounded-lg border p-4"
        >
            <div className="grid gap-2">
                <Label htmlFor="passkey-name">{t('passkeys.name')}</Label>
                <Input
                    id="passkey-name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder={t('passkeys.name_placeholder')}
                    required
                    maxLength={255}
                    autoFocus
                    disabled={busy}
                />
                <p className="text-sm text-muted-foreground">
                    {t('passkeys.name_hint')}
                </p>
            </div>
            <PasskeyFeedback error={confirmationError ?? errorInstance} />
            {(confirmationError || errorInstance) && (
                <Button
                    type="button"
                    variant="link"
                    onClick={() => router.visit(edit())}
                >
                    {t('passkeys.confirm_again')}
                </Button>
            )}
            <div className="flex gap-2">
                <Button type="submit" disabled={busy || !name.trim()}>
                    {busy && <LoaderCircle className="animate-spin" />}
                    {t(busy ? 'passkeys.registering' : 'passkeys.register')}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    disabled={busy}
                    onClick={() => {
                        setShowForm(false);
                        setName('');
                    }}
                >
                    {t('passkeys.cancel')}
                </Button>
            </div>
        </form>
    );
}
