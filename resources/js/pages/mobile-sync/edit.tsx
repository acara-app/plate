import HeadingSmall from '@/components/heading-small';
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
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import {
    destroy as mobileSyncDestroy,
    edit as mobileSyncEdit,
    token as mobileSyncToken,
} from '@/routes/mobile-sync';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { Check, Copy, QrCode, Smartphone, Trash2 } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Device {
    id: number;
    device_name: string | null;
    paired_at: string | null;
    last_synced_at: string | null;
}

interface Props {
    devices: Device[];
    pending_token: string | null;
    token_expires_at: string | null;
    instance_url: string;
    [key: string]: unknown;
}

export default function Edit({
    devices,
    pending_token,
    token_expires_at,
    instance_url,
}: Props) {
    const { t } = useTranslation('common');
    const [copiedToken, setCopiedToken] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('mobile_sync.title'),
            href: mobileSyncEdit.url(),
        },
    ];

    const handleCopyToken = async () => {
        if (pending_token) {
            await navigator.clipboard?.writeText(pending_token);
            setCopiedToken(true);
            setTimeout(() => setCopiedToken(false), 2000);
        }
    };

    const formatDate = (dateString: string | null | undefined) => {
        if (!dateString) {
            return t('mobile_sync.never_synced');
        }
        return new Date(dateString).toLocaleString();
    };

    const hasPairedDevices = devices.length > 0;
    const hasPendingToken = pending_token !== null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('mobile_sync.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('mobile_sync.title')}
                        description={t('mobile_sync.description')}
                    />

                    {hasPendingToken && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/20">
                                        <QrCode className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <CardTitle>
                                            {t('mobile_sync.pairing_code')}
                                        </CardTitle>
                                        <CardDescription>
                                            {t('mobile_sync.scan_qr')}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex justify-center">
                                    <div className="rounded-xl border bg-white p-4">
                                        <QRCodeSVG
                                            value={JSON.stringify({
                                                instance: instance_url,
                                                token: pending_token,
                                            })}
                                            size={200}
                                            level="M"
                                        />
                                    </div>
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <div>
                                        <Label className="text-sm font-medium">
                                            {t('mobile_sync.or_enter_token')}
                                        </Label>
                                        <div className="mt-2 flex gap-2">
                                            <Input
                                                value={pending_token}
                                                readOnly
                                                className="font-mono text-lg tracking-widest"
                                            />
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                onClick={handleCopyToken}
                                            >
                                                {copiedToken ? (
                                                    <Check className="h-4 w-4 text-green-600" />
                                                ) : (
                                                    <Copy className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                    </div>

                                    <p className="text-xs text-muted-foreground">
                                        {t('mobile_sync.expires_at')}:{' '}
                                        {formatDate(token_expires_at)}
                                    </p>
                                </div>

                                <Separator />

                                <div className="space-y-2">
                                    <h4 className="text-sm font-medium">
                                        {t('mobile_sync.steps_title')}
                                    </h4>
                                    <ol className="list-decimal space-y-1 pl-4 text-sm text-muted-foreground">
                                        <li>{t('mobile_sync.step_1')}</li>
                                        <li>{t('mobile_sync.step_2')}</li>
                                        <li>{t('mobile_sync.step_3')}</li>
                                        <li>{t('mobile_sync.step_4')}</li>
                                    </ol>
                                </div>

                                <Form {...mobileSyncToken.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {t('mobile_sync.regenerate_token')}
                                        </Button>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {!hasPendingToken && !hasPairedDevices && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/20">
                                        <Smartphone className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <CardTitle>
                                            {t('mobile_sync.no_devices')}
                                        </CardTitle>
                                        <CardDescription>
                                            {t(
                                                'mobile_sync.no_devices_description',
                                            )}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Form {...mobileSyncToken.form()}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Smartphone className="h-4 w-4" />
                                            {t('mobile_sync.generate_token')}
                                        </Button>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {hasPairedDevices && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/20">
                                            <Smartphone className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <CardTitle>
                                            {t('mobile_sync.paired_devices')}
                                        </CardTitle>
                                    </div>
                                    {!hasPendingToken && (
                                        <Form {...mobileSyncToken.form()}>
                                            {({ processing }) => (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    <Smartphone className="h-4 w-4" />
                                                    {t(
                                                        'mobile_sync.generate_token',
                                                    )}
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {devices.map((device) => (
                                    <div
                                        key={device.id}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium">
                                                {device.device_name ??
                                                    'Unknown device'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {t('mobile_sync.paired_at')}:{' '}
                                                {formatDate(device.paired_at)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {t('mobile_sync.last_synced')}:{' '}
                                                {formatDate(
                                                    device.last_synced_at,
                                                )}
                                            </p>
                                        </div>
                                        <Form
                                            {...mobileSyncDestroy.form(
                                                device.id,
                                            )}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    {t(
                                                        'mobile_sync.disconnect',
                                                    )}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
