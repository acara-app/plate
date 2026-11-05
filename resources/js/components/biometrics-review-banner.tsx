import { Button } from '@/components/ui/button';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import onboarding from '@/routes/onboarding';
import { Link } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface BiometricsReviewBannerProps {
    className?: string;
}

export function BiometricsReviewBanner({
    className,
}: BiometricsReviewBannerProps) {
    const { t } = useTranslation('common');
    const { needsBiometricsReview } = useSharedProps();

    if (!needsBiometricsReview) {
        return null;
    }

    return (
        <div
            data-testid="biometrics-review-banner"
            role="status"
            className={cn(
                'flex items-center justify-between gap-3 rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-sm',
                className,
            )}
        >
            <div className="flex items-center gap-2">
                <Scale className="h-4 w-4 shrink-0 text-primary" />
                <span className="text-muted-foreground">
                    {t('biometrics_review_banner.text')}
                </span>
            </div>
            <Button
                asChild
                size="sm"
                variant="ghost"
                className="h-7 shrink-0 text-xs"
            >
                <Link href={onboarding.biometrics.show.url()}>
                    {t('biometrics_review_banner.action')}
                </Link>
            </Button>
        </div>
    );
}
