import {
    PhotoAllowanceCard,
    type PhotoAllowance,
} from '@/components/photo-allowance';
import AnalyzeSnapToTrackPhotoController from '@/actions/App/Http/Controllers/SnapToTrack/AnalyzeSnapToTrackPhotoController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import {
    BurstLimitNotice,
    type BurstLimit,
} from '@/components/billing/burst-limit-notice';
import { MealPhotoPicker } from '@/components/snap-to-track/meal-photo-picker';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import checkout from '@/routes/checkout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Gauge, LoaderCircle, ScanLine } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

type CreditLimit = {
    limit_type: string;
    tier: string;
    tier_label: string;
    current_credits: number;
    limit_credits: number;
    resets_at: string;
    resets_in: string;
};

interface SnapToTrackIndexProps {
    photoAllowance?: PhotoAllowance;
    analysisRequestId: string;
    creditLimit: CreditLimit | null;
    burstLimit: BurstLimit | null;
}

export default function SnapToTrackIndex({
    creditLimit,
    burstLimit,
    photoAllowance,
    analysisRequestId,
}: SnapToTrackIndexProps) {
    const { t } = useTranslation('common');
    const [photo, setPhoto] = useState<File | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('snap_to_track.title'),
            href: ShowSnapToTrackController().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('snap_to_track.title')} />
            <div className="mx-auto flex w-full max-w-2xl min-w-0 flex-col gap-6 px-4 pt-4 pb-12 sm:px-6 sm:pt-6">
                <h1 className="sr-only">{t('snap_to_track.title')}</h1>
                <PhotoAllowanceCard
                    allowance={photoAllowance}
                    variant="plain"
                />
                {burstLimit !== null && <BurstLimitNotice limit={burstLimit} />}
                {creditLimit !== null && (
                    <section className="flex flex-col gap-3 border-b pb-6">
                        <div className="flex items-start gap-3">
                            <Gauge
                                className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-500"
                                aria-hidden="true"
                            />
                            <div className="flex flex-col gap-1">
                                <h2 className="text-sm font-semibold">
                                    {creditLimit.tier === 'plus'
                                        ? t('snap_to_track.credit.pro_heading')
                                        : t('snap_to_track.credit.heading')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('snap_to_track.credit.body', {
                                        used: creditLimit.current_credits,
                                        limit: creditLimit.limit_credits,
                                        time: creditLimit.resets_in,
                                    })}
                                </p>
                            </div>
                        </div>
                        {creditLimit.tier !== 'plus' && (
                            <div className="flex sm:pl-8">
                                <Button asChild>
                                    <Link
                                        href={checkout.subscription().url}
                                        data-umami-event="snap_to_track_upgrade_click"
                                        data-umami-event-tier={creditLimit.tier}
                                    >
                                        {t('snap_to_track.credit.upgrade')}
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </section>
                )}

                {burstLimit === null &&
                    !(photoAllowance?.enabled && photoAllowance.exhausted) && (
                        <Form
                            {...AnalyzeSnapToTrackPhotoController.form()}
                            transform={(data) => ({ ...data, photo })}
                            disableWhileProcessing
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors, clearErrors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="analysis_request_id"
                                        value={analysisRequestId}
                                    />
                                    <MealPhotoPicker
                                        photo={photo}
                                        onChange={(file) => {
                                            setPhoto(file);
                                            clearErrors('photo');
                                        }}
                                        disabled={processing}
                                        error={errors.photo}
                                    />
                                    <div className="flex flex-col gap-3 border-t pt-5">
                                        <Button
                                            type="submit"
                                            size="touch"
                                            className="w-full shadow-none before:hidden after:hidden hover:shadow-none"
                                            disabled={processing || !photo}
                                        >
                                            {processing ? (
                                                <LoaderCircle
                                                    className="animate-spin"
                                                    aria-hidden="true"
                                                />
                                            ) : (
                                                <ScanLine aria-hidden="true" />
                                            )}
                                            {processing
                                                ? t(
                                                      'snap_to_track.index.analyzing',
                                                  )
                                                : t(
                                                      'snap_to_track.index.analyze',
                                                  )}
                                        </Button>
                                        <p className="text-center text-xs leading-relaxed text-muted-foreground">
                                            {t(
                                                'snap_to_track.review.disclaimer',
                                            )}
                                        </p>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}
            </div>
        </AppLayout>
    );
}
