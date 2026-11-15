import {
    AUTH_ICON_BUTTON,
    AUTH_TEXT_LINK,
    AuthBrand,
} from '@/components/auth/auth-screen';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export const ONBOARDING_SURFACE = 'bg-foreground/[0.045] dark:bg-white/[0.06]';

export const ONBOARDING_RISE =
    'duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] fill-mode-both motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2';

export const ONBOARDING_COLUMNS =
    'mt-7 grid flex-1 grid-cols-1 content-start gap-7 md:mt-10 md:grid-cols-2 md:items-start md:gap-x-10';

export const ONBOARDING_LABEL =
    'mb-2.5 text-[0.9375rem] font-semibold text-muted-foreground';

export function OnboardingHeader({
    step,
    backHref,
}: {
    step?: number;
    backHref?: string;
}) {
    const { t } = useTranslation('common');

    return (
        <header className="flex h-11 items-center gap-4">
            {backHref ? (
                <Link
                    href={backHref}
                    aria-label={t('back')}
                    className={AUTH_ICON_BUTTON}
                >
                    <ArrowLeft className="size-5" aria-hidden="true" />
                </Link>
            ) : (
                <AuthBrand />
            )}
            {step !== undefined && (
                <div
                    role="progressbar"
                    aria-label={t('onboarding.biometrics.step', {
                        current: step,
                        total: 3,
                    })}
                    aria-valuemin={1}
                    aria-valuemax={3}
                    aria-valuenow={step}
                    className="ml-auto flex w-28 gap-1.5"
                >
                    {[1, 2, 3].map((index) => (
                        <span
                            key={index}
                            className={cn(
                                'h-1.5 flex-1 rounded-full',
                                index <= step
                                    ? 'bg-foreground'
                                    : 'bg-foreground/10',
                            )}
                        />
                    ))}
                </div>
            )}
            <Link
                href={dashboard.url()}
                className={cn(
                    AUTH_TEXT_LINK,
                    'text-sm font-medium text-muted-foreground no-underline',
                    step === undefined && 'ml-auto',
                )}
            >
                {t('onboarding.biometrics.exit')}
            </Link>
        </header>
    );
}
