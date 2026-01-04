import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import clsx from 'clsx';
import { useTranslation } from 'react-i18next';

import HeadingSmall from '@/components/heading-small';
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

interface Props {
    billingHistory: Invoice[];
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('billing.title'),
        href: billing.index().url,
    },
];

export default function Index({ billingHistory }: Props) {
    const { t } = useTranslation('common');
    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('billing.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('billing.title')}
                        description={t('billing.description')}
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
