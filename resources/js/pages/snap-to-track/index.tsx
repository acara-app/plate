import {
    PhotoAllowanceCard,
    type PhotoAllowance,
} from '@/components/photo-allowance';
import AnalyzeSnapToTrackPhotoController from '@/actions/App/Http/Controllers/SnapToTrack/AnalyzeSnapToTrackPhotoController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { AiAllowanceNotice } from '@/components/billing/ai-allowance';
import { MealPhotoPicker } from '@/components/snap-to-track/meal-photo-picker';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type AiAllowance, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, ScanLine } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface SnapToTrackIndexProps {
    photoAllowance?: PhotoAllowance;
    analysisRequestId: string;
    aiAllowance: AiAllowance | null;
}

export default function SnapToTrackIndex({
    aiAllowance,
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
                {aiAllowance && <AiAllowanceNotice allowance={aiAllowance} />}

                {!aiAllowance?.exhausted &&
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
