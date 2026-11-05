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
                'flex min-w-0 flex-col items-stretch gap-3 rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-sm sm:flex-row sm:items-center sm:justify-between',
                className,
            )}
        >
            <div className="flex min-w-0 items-start gap-2">
                <Scale className="h-4 w-4 shrink-0 text-primary" />
                <span className="min-w-0 wrap-break-word text-muted-foreground">
                    {t('biometrics_review_banner.text')}
                </span>
            </div>
            <Button
                asChild
                size="sm"
                variant="ghost"
                className="h-auto min-h-11 shrink-0 py-2 whitespace-normal sm:w-auto"
            >
                <Link href={onboarding.biometrics.show.url()}>
                    {t('biometrics_review_banner.action')}
                </Link>
            </Button>
        </div>
    );
}
