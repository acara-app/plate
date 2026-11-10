import { useTranslation } from 'react-i18next';
import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import checkout from '@/routes/checkout';
import { snapToTrack } from '@/routes';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ActivityIcon, ChevronDown, ScanLine, Sparkles } from 'lucide-react';
import { useEffect, useRef } from 'react';

const seenOffers = new Set<string>();

export interface PhotoAllowance {
    enabled: boolean;
    limit: number | null;
    used: number;
    remaining: number | null;
    exhausted: boolean;
    resets_at: string | null;
    mode: string;
    can_upgrade: boolean;
    variant?: string | null;
    preview?: {
        status: 'available' | 'active' | 'expired';
        starts_at: string | null;
        expires_at: string | null;
        next_scan_at: string | null;
    } | null;
    offer?: {
        product_id: number;
        name: string;
        formatted_price: string;
    } | null;
}

export function PhotoAllowanceCard({
    allowance,
    showUpgrade = true,
    variant = 'card',
    className,
}: {
    allowance?: PhotoAllowance;
    showUpgrade?: boolean;
    variant?: 'card' | 'scanner';
    className?: string;
}) {
    const { t } = useTranslation('common');
    const offerRef = useRef<HTMLDivElement>(null);
    const showOffer = !!(
        showUpgrade &&
        allowance?.can_upgrade &&
        allowance.offer &&
        (allowance.exhausted || allowance.preview)
    );
    useEffect(() => {
        if (!showOffer || !offerRef.current || !allowance) {
            return;
        }
        const key = JSON.stringify([
            allowance.variant,
            allowance.mode,
            allowance.used,
            allowance.resets_at,
        ]);
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting && !seenOffers.has(key)) {
                    seenOffers.add(key);
                    window.umami?.track('snap_to_track_offer_viewed', {
                        source: 'app',
                        mode: allowance.mode,
                        variant: allowance.variant ?? 'baseline',
                    });
                    observer.disconnect();
                }
            },
            { threshold: 0.5 },
        );
        observer.observe(offerRef.current);
        return () => observer.disconnect();
    }, [showOffer, allowance]);
    if (!allowance?.enabled || allowance.limit === null) {
        return null;
    }

    const remaining = allowance.remaining ?? 0;
    const trial = allowance.mode === 'trial';
    const offer = allowance.offer;
    const scanner = variant === 'scanner';
    const muted = scanner ? 'text-white/60' : 'text-muted-foreground';

    return (
        <Card
            className={cn(
                scanner &&
                    'gap-4 rounded-[2rem] border-0 bg-neutral-950 p-6 text-white shadow-[0_36px_70px_-36px_rgb(0_0_0/0.6)] ring-1 ring-white/10 sm:p-8 dark:bg-neutral-900',
                className,
            )}
        >
            <CardHeader className={cn(scanner && 'gap-5 px-0')}>
                {scanner && (
                    <span
                        aria-hidden="true"
                        className="flex size-12 items-center justify-center rounded-2xl bg-white/10 text-primary"
                    >
                        <ScanLine className="size-6" />
                    </span>
                )}
                <CardTitle
                    className={cn(
                        scanner &&
                            'text-2xl leading-tight font-bold tracking-tight text-balance',
                    )}
                >
                    {trial
                        ? remaining > 0
                            ? t('photo_plans.free.remaining', {
                                  count: remaining,
                              })
                            : t('photo_plans.free.used')
                        : t('photo_plans.offer.remaining', {
                              remaining,
                              total: allowance.limit,
                          })}
                </CardTitle>
            </CardHeader>
            <CardContent className={cn('space-y-3', scanner && 'px-0')}>
                {trial && (
                    <p className={cn('text-sm', muted)}>
                        {t('photo_plans.free.total', {
                            count: allowance.limit,
                        })}
                    </p>
                )}
                {allowance.resets_at && (
                    <p className={cn('text-sm', muted)}>
                        {t('photo_plans.offer.resets', {
                            time: new Date(
                                allowance.resets_at,
                            ).toLocaleString(),
                        })}
                    </p>
                )}
                {allowance.preview?.status === 'available' && (
                    <Button asChild className="min-h-11">
                        <a href={snapToTrack().url}>
                            {t('photo_plans.preview.try')}
                        </a>
                    </Button>
                )}
                {allowance.preview?.expires_at && (
                    <p className={cn('text-sm', muted)}>
                        {t('photo_plans.preview.ends', {
                            time: new Date(
                                allowance.preview.expires_at,
                            ).toLocaleString(),
                        })}
                    </p>
                )}
                {showOffer && offer && (
                    <div
                        ref={offerRef}
                        className={cn('space-y-3', scanner && 'pt-2')}
                    >
                        <p>
                            {t('photo_plans.offer.body', { name: offer.name })}
                        </p>
                        <Button
                            asChild
                            className={cn(
                                'h-auto min-h-11 text-center whitespace-normal',
                                scanner &&
                                    'min-h-14 w-full rounded-full px-6 text-base',
                            )}
                        >
                            <Link
                                href={checkout.start(offer.product_id).url}
                                data-umami-event="snap_to_track_upgrade_click"
                            >
                                {t('photo_plans.offer.cta', {
                                    name: offer.name,
                                    price: offer.formatted_price,
                                })}
                            </Link>
                        </Button>
                        <p className={cn('text-xs', muted)}>
                            {t('photo_plans.offer.terms')}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export function PhotoAllowanceMenu({
    allowance,
    className,
}: {
    allowance?: PhotoAllowance;
    className?: string;
}) {
    const { t } = useTranslation('common');

    if (
        !allowance?.enabled ||
        allowance.limit === null ||
        allowance.exhausted ||
        allowance.preview
    ) {
        return null;
    }

    const remaining = allowance.remaining ?? 0;
    const trial = allowance.mode === 'trial';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                data-test="photo-allowance-menu"
                className={cn(
                    'inline-flex h-12 max-w-full cursor-pointer touch-manipulation items-center gap-2 rounded-full pr-3.5 pl-4 text-sm font-medium text-foreground transition-[scale] duration-150 ease-out outline-none select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 active:scale-[0.97] motion-reduce:transition-none dark:focus-visible:ring-emerald-400',
                    className,
                )}
            >
                <ScanLine
                    className="size-4 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <span className="truncate">
                    {trial
                        ? t('photo_plans.free.remaining', { count: remaining })
                        : t('photo_plans.offer.remaining', {
                              remaining,
                              total: allowance.limit,
                          })}
                </span>
                <ChevronDown
                    className="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-72">
                {(trial || allowance.resets_at) && (
                    <>
                        <DropdownMenuLabel className="flex flex-col gap-1 text-xs leading-5 font-normal text-muted-foreground">
                            {trial && (
                                <span>
                                    {t('photo_plans.free.total', {
                                        count: allowance.limit,
                                    })}
                                </span>
                            )}
                            {allowance.resets_at && (
                                <span>
                                    {t('photo_plans.offer.resets', {
                                        time: new Date(
                                            allowance.resets_at,
                                        ).toLocaleString(),
                                    })}
                                </span>
                            )}
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                    </>
                )}
                <DropdownMenuItem asChild>
                    <Link href={ListHealthEntryController().url}>
                        <ActivityIcon className="size-4" aria-hidden="true" />
                        {t('snap_to_track.index.open_entry')}
                    </Link>
                </DropdownMenuItem>
                {allowance.can_upgrade && (
                    <DropdownMenuItem asChild>
                        <Link href={checkout.subscription().url}>
                            <Sparkles className="size-4" aria-hidden="true" />
                            {t('billing.allowance.see_plans')}
                        </Link>
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
