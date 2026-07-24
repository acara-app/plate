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
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, ScanLine } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface SnapToTrackIndexProps {
    savedGroupId: string | null;
}

export default function SnapToTrackIndex({
    savedGroupId,
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
