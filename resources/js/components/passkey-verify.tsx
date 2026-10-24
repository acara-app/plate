import PasskeyFeedback from '@/components/passkey-feedback';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { confirm, confirmOptions, login, loginOptions } from '@/routes/passkey';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound, LoaderCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

type Props = {
    confirmation?: boolean;
    remember?: boolean;
    disabled?: boolean;
    onProcessingChange?: (processing: boolean) => void;
};

export default function PasskeyVerify({
    confirmation = false,
    remember = false,
    disabled = false,
    onProcessingChange,
}: Props) {
    const { t } = useTranslation('auth');
    const { verify, isLoading, errorInstance, isSupported } = usePasskeyVerify({
        remember,
        routes: {
            options: confirmation ? confirmOptions.url() : loginOptions.url(),
            submit: confirmation ? confirm.url() : login.url(),
        },
        onSuccess: (response) =>
            router.visit(response.redirect ?? dashboard.url()),
    });

    if (!isSupported) {
        return null;
    }

    const handleVerify = async () => {
        onProcessingChange?.(true);
        try {
            await verify();
        } finally {
            onProcessingChange?.(false);
        }
    };

    return (
        <div className="grid gap-2">
            <Button
                type="button"
                variant="outline"
                className="w-full"
                onClick={handleVerify}
                disabled={disabled || isLoading}
                data-test={confirmation ? 'confirm-passkey' : 'login-passkey'}
            >
                {isLoading ? (
                    <LoaderCircle className="animate-spin" />
                ) : (
                    <KeyRound />
                )}
                {t(
                    `passkeys.${isLoading ? 'verifying' : confirmation ? 'confirm' : 'login'}`,
                )}
            </Button>
            <PasskeyFeedback error={errorInstance} />
        </div>
    );
}
