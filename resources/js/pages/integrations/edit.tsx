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
import { edit as integrationsEdit } from '@/routes/integrations';
import {
    destroy as telegramDestroy,
    token as telegramToken,
} from '@/routes/integrations/telegram';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import { Check, Copy, ExternalLink, MessageCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface TelegramData {
    is_connected: boolean;
    linking_token: string | null;
    token_expires_at: string | null;
    connected_at: string | null;
    bot_username: string;
}

interface Props {
    telegram: TelegramData;
    telegram_token?: string;
    token_expires_at?: string;
    bot_username: string;
    [key: string]: unknown;
}

export default function Edit() {
    const { t } = useTranslation('common');
    const { telegram, telegram_token, token_expires_at } =
        usePage<Props>().props;
    const [copied, setCopied] = useState(false);
    const [botNameCopied, setBotNameCopied] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('integrations.title'),
            href: integrationsEdit.url(),
        },
    ];

    const effectiveToken = telegram_token || telegram.linking_token;
    const effectiveExpiresAt = token_expires_at || telegram.token_expires_at;

    const handleCopyToken = () => {
        if (effectiveToken) {
            navigator.clipboard.writeText(`/link ${effectiveToken}`);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const handleCopyBotName = () => {
        navigator.clipboard.writeText(telegram.bot_username);
        setBotNameCopied(true);
        setTimeout(() => setBotNameCopied(false), 2000);
    };

    const formatDate = (dateString: string | null | undefined) => {
        if (!dateString) {
            return 'N/A';
        }
        return new Date(dateString).toLocaleString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('integrations.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('integrations.title')}
                        description={t('integrations.description')}
                    />

                    {/* Telegram Integration */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                    <MessageCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <CardTitle>
                                        {t('integrations.telegram.title')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t('integrations.telegram.description')}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {telegram.is_connected ? (
                                <>
                                    <div className="flex items-center gap-2 text-sm text-green-600">
                                        <Check className="h-4 w-4" />
                                        <span>
                                            {t(
                                                'integrations.telegram.connected',
                                            )}{' '}
                                            {formatDate(telegram.connected_at)}
                                        </span>
                                    </div>

                                    <Separator />

                                    <div className="space-y-2">
                                        <h4 className="text-sm font-medium">
                                            {t(
                                                'integrations.telegram.how_to_use',
                                            )}
                                        </h4>
                                        <ol className="list-decimal space-y-1 pl-4 text-sm text-muted-foreground">
                                            <li className="flex items-center gap-1">
                                                {t(
                                                    'integrations.telegram.step_1',
                                                )}{' '}
                                                <strong>
                                                    @{telegram.bot_username}
                                                </strong>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="ml-1 h-4 w-4"
                                                    onClick={handleCopyBotName}
                                                >
                                                    {botNameCopied ? (
                                                        <Check className="h-3 w-3 text-green-600" />
                                                    ) : (
                                                        <Copy className="h-3 w-3" />
                                                    )}
                                                </Button>
                                            </li>
                                            <li>
                                                {t(
                                                    'integrations.telegram.step_2',
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    'integrations.telegram.step_3',
                                                )}
                                            </li>
                                        </ol>
                                    </div>

                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={`https://t.me/${telegram.bot_username}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <ExternalLink className="mr-2 h-4 w-4" />
                                                {t(
                                                    'integrations.telegram.open_bot',
                                                )}
                                            </a>
                                        </Button>

                                        <Form {...telegramDestroy.form()}>
                                            {({ processing }) => (
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    {t(
                                                        'integrations.telegram.disconnect',
                                                    )}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </>
                            ) : telegram_token || telegram.linking_token ? (
                                <>
                                    <div className="rounded-lg bg-muted p-4">
                                        <Label className="text-sm font-medium">
                                            {t(
                                                'integrations.telegram.your_token',
                                            )}
                                        </Label>
                                        <div className="mt-2 flex gap-2">
                                            <Input
                                                value={`/link ${effectiveToken}`}
                                                readOnly
                                                className="font-mono"
                                            />
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                onClick={handleCopyToken}
                                            >
                                                {copied ? (
                                                    <Check className="h-4 w-4 text-green-600" />
                                                ) : (
                                                    <Copy className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            {t(
                                                'integrations.telegram.expires_at',
                                            )}
                                            : {formatDate(effectiveExpiresAt)}
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="text-sm font-medium">
                                            {t(
                                                'integrations.telegram.next_steps',
                                            )}
                                        </h4>
                                        <ol className="list-decimal space-y-1 pl-4 text-sm text-muted-foreground">
                                            <li>
                                                {t(
                                                    'integrations.telegram.copy_token',
                                                )}
                                            </li>
                                            <li className="flex items-center gap-1">
                                                {t(
                                                    'integrations.telegram.step_1',
                                                )}{' '}
                                                <strong>
                                                    @{telegram.bot_username}
                                                </strong>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="ml-1 h-4 w-4"
                                                    onClick={handleCopyBotName}
                                                >
                                                    {botNameCopied ? (
                                                        <Check className="h-3 w-3 text-green-600" />
                                                    ) : (
                                                        <Copy className="h-3 w-3" />
                                                    )}
                                                </Button>
                                            </li>
                                            <li>
                                                {t(
                                                    'integrations.telegram.send_token',
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    'integrations.telegram.step_3',
                                                )}
                                            </li>
                                        </ol>
                                    </div>

                                    <Button variant="outline" size="sm" asChild>
                                        <a
                                            href={`https://t.me/${telegram.bot_username}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <ExternalLink className="mr-2 h-4 w-4" />
                                            {t(
                                                'integrations.telegram.open_bot',
                                            )}
                                        </a>
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'integrations.telegram.connect_description',
                                        )}
                                    </p>

                                    <Form {...telegramToken.form()}>
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                <MessageCircle className="mr-2 h-4 w-4" />
                                                {t(
                                                    'integrations.telegram.connect',
                                                )}
                                            </Button>
                                        )}
                                    </Form>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Future integrations placeholder */}
                    <Card className="opacity-50">
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                    <MessageCircle className="h-5 w-5 text-gray-400" />
                                </div>
                                <div>
                                    <CardTitle>
                                        {t('integrations.whatsapp.title')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t('integrations.whatsapp.coming_soon')}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                {t('integrations.whatsapp.description')}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
