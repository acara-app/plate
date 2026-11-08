import AcknowledgePrivacyPolicyController from '@/actions/App/Http/Controllers/AcknowledgePrivacyPolicyController';
import { Button } from '@/components/ui/button';
import { privacy } from '@/routes';
import { router } from '@inertiajs/react';
import { ShieldCheck, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export function PrivacyUpdateNotice() {
    const { t } = useTranslation('common');

    function acknowledge(): void {
        router.post(
            AcknowledgePrivacyPolicyController.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['showPrivacyUpdate'],
                optimistic: () => ({ showPrivacyUpdate: false }),
            },
        );
    }

    return (
        <div className="flex items-start justify-between gap-3 rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-sm">
            <div className="flex items-start gap-2">
                <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                <p className="text-muted-foreground">
                    {t('privacy_update_notice.text')}{' '}
                    <a
                        href={privacy.url()}
                        className="font-medium text-foreground underline underline-offset-4 hover:text-primary"
                    >
                        {t('privacy_update_notice.link')}
                    </a>
                </p>
            </div>
            <Button
                size="sm"
                variant="ghost"
                className="h-7 w-7 shrink-0 p-0"
                onClick={acknowledge}
                aria-label={t('privacy_update_notice.dismiss')}
            >
                <X className="h-4 w-4" />
            </Button>
        </div>
    );
}
