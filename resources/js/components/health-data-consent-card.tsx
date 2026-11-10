import AcceptHealthDataConsentController from '@/actions/App/Http/Controllers/AcceptHealthDataConsentController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { privacy } from '@/routes';
import { router } from '@inertiajs/react';
import { HeartPulse } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export function HealthDataConsentCard({ className }: { className?: string }) {
    const { t } = useTranslation('common');
    const [postponed, setPostponed] = useState(false);

    if (postponed) {
        return null;
    }

    function agree(): void {
        router.post(
            AcceptHealthDataConsentController.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['showHealthDataConsent', 'showPrivacyUpdate'],
                optimistic: () => ({
                    showHealthDataConsent: false,
                    showPrivacyUpdate: false,
                }),
            },
        );
    }

    return (
        <div
            className={cn(
                'flex flex-col gap-3 rounded-md border border-primary/20 bg-primary/5 px-4 py-3 text-sm',
                className,
            )}
        >
            <div className="flex items-start gap-2">
                <HeartPulse className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                <div className="space-y-1">
                    <p className="font-medium text-foreground">
                        {t('health_data_consent_prompt.title')}
                    </p>
                    <p className="text-muted-foreground">
                        {t('register.health_data_consent', { ns: 'auth' })}
                    </p>
                    <p className="text-muted-foreground">
                        {t('health_data_consent_prompt.policy_updated')}{' '}
                        <a
                            href={privacy.url()}
                            className="font-medium text-foreground underline underline-offset-4 hover:text-primary"
                        >
                            {t('health_data_consent_prompt.policy_link')}
                        </a>
                    </p>
                </div>
            </div>
            <div className="flex flex-wrap gap-2 pl-6">
                <Button size="sm" onClick={agree}>
                    {t('health_data_consent_prompt.agree')}
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => setPostponed(true)}
                >
                    {t('health_data_consent_prompt.later')}
                </Button>
            </div>
        </div>
    );
}
