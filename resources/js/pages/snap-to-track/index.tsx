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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import checkout from '@/routes/checkout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Gauge, ScanLine } from 'lucide-react';
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
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
                <PhotoAllowanceCard allowance={photoAllowance} />
                {burstLimit !== null && <BurstLimitNotice limit={burstLimit} />}
                {creditLimit !== null && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Gauge className="size-5 text-amber-600 dark:text-amber-500" />
                                {creditLimit.tier === 'plus'
                                    ? t('snap_to_track.credit.pro_heading')
                                    : t('snap_to_track.credit.heading')}
                            </CardTitle>
                            <CardDescription>
                                {t('snap_to_track.credit.body', {
                                    used: creditLimit.current_credits,
                                    limit: creditLimit.limit_credits,
                                    time: creditLimit.resets_in,
                                })}
                            </CardDescription>
                        </CardHeader>
                        {creditLimit.tier !== 'plus' && (
                            <CardContent className="flex flex-col gap-3 sm:flex-row">
                                <Button asChild>
                                    <Link
                                        href={checkout.subscription().url}
                                        data-umami-event="snap_to_track_upgrade_click"
                                        data-umami-event-tier={creditLimit.tier}
                                    >
                                        {t('snap_to_track.credit.upgrade')}
                                    </Link>
                                </Button>
                            </CardContent>
                        )}
                    </Card>
                )}

                {burstLimit === null &&
                    !(photoAllowance?.enabled && photoAllowance.exhausted) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ScanLine className="size-5" />
                                    {t('snap_to_track.index.heading')}
                                </CardTitle>
                                <CardDescription>
                                    {t('snap_to_track.index.description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...AnalyzeSnapToTrackPhotoController.form()}
                                    transform={(data) => ({ ...data, photo })}
                                    disableWhileProcessing
                                    className="flex flex-col gap-4"
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
                                            <Button
                                                type="submit"
                                                size="touch"
                                                className="w-full"
                                                disabled={processing || !photo}
                                            >
                                                {processing
                                                    ? t(
                                                          'snap_to_track.index.analyzing',
                                                      )
                                                    : t(
                                                          'snap_to_track.index.analyze',
                                                      )}
                                            </Button>
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'snap_to_track.review.disclaimer',
                                                )}
                                            </p>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
            </div>
        </AppLayout>
    );
}
