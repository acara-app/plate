import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import AnalyzeSnapToTrackPhotoController from '@/actions/App/Http/Controllers/SnapToTrack/AnalyzeSnapToTrackPhotoController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import checkout from '@/routes/checkout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, Gauge, ScanLine } from 'lucide-react';
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
    savedGroupId: string | null;
    creditLimit: CreditLimit | null;
}

export default function SnapToTrackIndex({
    savedGroupId,
    creditLimit,
}: SnapToTrackIndexProps) {
    const { t } = useTranslation('common');

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
                {savedGroupId !== null && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle2 className="size-5 text-green-600" />
                                {t('snap_to_track.index.saved_heading')}
                            </CardTitle>
                            <CardDescription>
                                {t('snap_to_track.index.saved_description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 sm:flex-row">
                            <Button asChild>
                                <Link href={ListHealthEntryController().url}>
                                    {t('snap_to_track.index.open_entry')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

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
                            disableWhileProcessing
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <Label htmlFor="snap-photo">
                                            {t(
                                                'snap_to_track.index.upload_label',
                                            )}
                                        </Label>
                                        <Input
                                            id="snap-photo"
                                            name="photo"
                                            type="file"
                                            accept="image/*"
                                            required
                                            className="mt-1"
                                        />
                                        <InputError message={errors.photo} />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        {processing
                                            ? t('snap_to_track.index.analyzing')
                                            : t('snap_to_track.index.analyze')}
                                    </Button>
                                    <p className="text-xs text-muted-foreground">
                                        {t('snap_to_track.review.disclaimer')}
                                    </p>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
