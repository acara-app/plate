import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import clsx from 'clsx';
import { useTranslation } from 'react-i18next';

import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { UsageWidget } from '@/components/usage-widget';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import billing from '@/routes/billing';

interface Invoice {
    id: string;
    date: string;
    total: string;
    status: string;
    download_url: string;
}

interface AiUsageData {
    current: number;
    limit: number;
    percentage: number;
    resets_in: string;
    over_limit: boolean;
}

interface AiUsage {
    tier: 'free' | 'basic' | 'plus';
    tier_label: string;
    payment_pending: boolean;
    premium_enforcement_active: boolean;
    rolling: AiUsageData;
    weekly: AiUsageData;
}

interface Props {
    billingHistory: Invoice[];
    aiUsage?: AiUsage;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('billing.title'),
        href: billing.index().url,
    },
];

export default function Index({ billingHistory, aiUsage }: Props) {
    const { t } = useTranslation('common');

    const showTierBadge = aiUsage?.premium_enforcement_active === true;

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('billing.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('billing.title')}
                        description={t('billing.description')}
                    />

                    {aiUsage && (
                        <div className="space-y-4">
                            {showTierBadge && (
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-xs font-medium text-muted-foreground">
                                        {t('billing.tier.current_plan')}
                                    </span>
                                    <Badge variant="secondary">
                                        {aiUsage.tier_label}
                                    </Badge>
                                    {aiUsage.payment_pending && (
                                        <Badge variant="destructive">
                                            {t('billing.tier.payment_pending')}
                                        </Badge>
                                    )}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <UsageWidget
                                    title={t('billing.usage.rolling')}
                                    currentAmount={aiUsage.rolling.current}
                                    limit={aiUsage.rolling.limit}
                                    resetsIn={aiUsage.rolling.resets_in}
                                    overLimit={aiUsage.rolling.over_limit}
                                />
                                <UsageWidget
                                    title={t('billing.usage.weekly')}
                                    currentAmount={aiUsage.weekly.current}
                                    limit={aiUsage.weekly.limit}
                                    resetsIn={aiUsage.weekly.resets_in}
                                    overLimit={aiUsage.weekly.over_limit}
                                />
                            </div>
                        </div>
                    )}

                    <HeadingSmall
                        title={t('billing.history.title')}
                        description={t('billing.history.description')}
                    />

                    {billingHistory.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                            <p className="text-sm text-muted-foreground">
                                {t('billing.no_history')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-lg border">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                            {t('billing.table.date')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                            {t('billing.table.amount')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                            {t('billing.table.status')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                            {t('billing.table.actions')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {billingHistory.map((invoice) => (
                                        <tr key={invoice.id}>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                {invoice.date}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                {invoice.total}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={clsx(
                                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                        invoice.status ===
                                                            'paid'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800',
                                                    )}
                                                >
                                                    {invoice.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                <a
                                                    href={invoice.download_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    {t(
                                                        'billing.table.download',
                                                    )}
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
