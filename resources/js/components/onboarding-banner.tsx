import { Button } from '@/components/ui/button';
import onboarding from '@/routes/onboarding';
import { Link } from '@inertiajs/react';
import { Sparkles, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export function OnboardingBanner() {
    const { t } = useTranslation('common');
    const [isDismissed, setIsDismissed] = useState(false);

    if (isDismissed) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-3 rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-sm">
            <div className="flex items-center gap-2">
                <Sparkles className="h-4 w-4 shrink-0 text-primary" />
                <span className="text-muted-foreground">
                    {t('onboarding_banner.text')}
                </span>
            </div>
            <div className="flex items-center gap-2">
                <Button
                    asChild
                    size="sm"
                    variant="ghost"
                    className="h-7 text-xs"
                >
                    <Link href={onboarding.biometrics.show.url()}>
                        {t('onboarding_banner.complete')}
                    </Link>
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    className="h-7 w-7 p-0"
                    onClick={() => setIsDismissed(true)}
                    aria-label={t('onboarding_banner.dismiss')}
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
