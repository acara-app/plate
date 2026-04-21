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
import {
    destroy as platformDestroy,
    token as platformToken,
} from '@/routes/integrations/platform';
import type { ChatPlatformIntegration } from '@/types/messaging';
import { Form } from '@inertiajs/react';
import { Check, Copy, ExternalLink, MessageCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Props {
    integration: ChatPlatformIntegration;
    pendingToken: string | null;
    pendingTokenExpiresAt: string | null;
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return 'N/A';
    }
    return new Date(value).toLocaleString();
}

export function ChatPlatformCard({
    integration,
    pendingToken,
    pendingTokenExpiresAt,
}: Props) {
    const { t } = useTranslation('common');
    const [copied, setCopied] = useState(false);
    const [botNameCopied, setBotNameCopied] = useState(false);

    const effectiveToken = pendingToken ?? integration.linking_token;
    const effectiveExpiresAt =
        pendingTokenExpiresAt ?? integration.token_expires_at;
    const copyableCommand = effectiveToken
        ? integration.linking_command.replace('YOUR_TOKEN', effectiveToken)
        : integration.linking_command;

    const keyBase = `integrations.${integration.platform}`;

    const handleCopyToken = () => {
        if (!effectiveToken) return;
        navigator.clipboard.writeText(copyableCommand);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleCopyBotName = () => {
        navigator.clipboard.writeText(integration.bot_username);
        setBotNameCopied(true);
        setTimeout(() => setBotNameCopied(false), 2000);
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                        <MessageCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <CardTitle>{t(`${keyBase}.title`)}</CardTitle>
                        <CardDescription>
                            {t(`${keyBase}.description`)}
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {integration.is_connected ? (
                    <>
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <Check className="h-4 w-4" />
                            <span>
                                {t(`${keyBase}.connected`)}{' '}
                                {formatDate(integration.connected_at)}
                            </span>
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <h4 className="text-sm font-medium">
                                {t(`${keyBase}.how_to_use`)}
                            </h4>
                            <ol className="list-decimal space-y-1 pl-4 text-sm text-muted-foreground">
                                <li className="flex items-center gap-1">
                                    {t(`${keyBase}.step_1`)}{' '}
                                    <strong>@{integration.bot_username}</strong>
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
                                <li>{t(`${keyBase}.step_2`)}</li>
                                <li>{t(`${keyBase}.step_3`)}</li>
                            </ol>
                        </div>

                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={integration.deep_link_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                    {t(`${keyBase}.open_bot`)}
                                </a>
                            </Button>

                            <Form
                                {...platformDestroy.form(integration.platform)}
                            >
                                {({ processing }) => (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        type="submit"
                                        disabled={processing}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        {t(`${keyBase}.disconnect`)}
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </>
                ) : effectiveToken ? (
                    <>
                        <div className="rounded-lg bg-muted p-4">
                            <Label className="text-sm font-medium">
                                {t(`${keyBase}.your_token`)}
                            </Label>
                            <div className="mt-2 flex gap-2">
                                <Input
                                    value={copyableCommand}
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
                                {t(`${keyBase}.expires_at`)}:{' '}
                                {formatDate(effectiveExpiresAt)}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <h4 className="text-sm font-medium">
                                {t(`${keyBase}.next_steps`)}
                            </h4>
                            <ol className="list-decimal space-y-1 pl-4 text-sm text-muted-foreground">
                                <li>{t(`${keyBase}.copy_token`)}</li>
                                <li className="flex items-center gap-1">
                                    {t(`${keyBase}.step_1`)}{' '}
                                    <strong>@{integration.bot_username}</strong>
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
                                <li>{t(`${keyBase}.send_token`)}</li>
                                <li>{t(`${keyBase}.step_3`)}</li>
                            </ol>
                        </div>

                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={integration.deep_link_url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink className="h-4 w-4" />
                                {t(`${keyBase}.open_bot`)}
                            </a>
                        </Button>
                    </>
                ) : (
                    <>
                        <p className="text-sm text-muted-foreground">
                            {t(`${keyBase}.connect_description`)}
                        </p>

                        <Form {...platformToken.form(integration.platform)}>
                            {({ processing }) => (
                                <Button type="submit" disabled={processing}>
                                    <MessageCircle className="h-4 w-4" />
                                    {t(`${keyBase}.connect`)}
                                </Button>
                            )}
                        </Form>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
