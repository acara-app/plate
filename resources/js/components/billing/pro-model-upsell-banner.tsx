import { Link } from '@inertiajs/react';
import { Sparkles, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import checkout from '@/routes/checkout';

const STORAGE_KEY = 'pro_model_upsell_dismissed';

export function ProModelUpsellBanner() {
    const { t } = useTranslation('common');
    const [dismissed, setDismissed] = useState(true);

    useEffect(() => {
        setDismissed(window.localStorage.getItem(STORAGE_KEY) === '1');
    }, []);

    if (dismissed) {
        return null;
    }

    const handleDismiss = () => {
        window.localStorage.setItem(STORAGE_KEY, '1');
        setDismissed(true);
    };

    return (
        <div className="flex items-start gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4 sm:p-5">
            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Sparkles className="size-4" />
            </span>
            <div className="flex-1 space-y-2">
                <p className="text-sm font-medium text-foreground">
                    {t('billing.pro_model_upsell.title')}
                </p>
                <p className="text-xs text-muted-foreground">
                    {t('billing.pro_model_upsell.body')}
                </p>
                <Button asChild size="sm">
                    <Link href={checkout.subscription().url}>
                        {t('billing.pro_model_upsell.cta')}
                    </Link>
                </Button>
            </div>
            <button
                type="button"
                onClick={handleDismiss}
                aria-label={t('close')}
                className="text-muted-foreground transition hover:text-foreground"
            >
                <X className="size-4" />
            </button>
        </div>
    );
}
