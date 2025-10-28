import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { support } from '@/routes';
import billing from '@/routes/billing';
import checkout from '@/routes/checkout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import clsx from 'clsx';
import { CreditCardIcon, ReceiptIcon, TriangleIcon } from 'lucide-react';
import { useState } from 'react';

interface BillingProduct {
    id: number;
    name: string;
    description: string;
    features: string[];
    price: number;
    yearly_price: number;
    stripe_price_id: string;
    yearly_stripe_price_id: string;
    popular: boolean;
    formatted_price: string;
    formatted_yearly_price: string;
    yearly_savings: number;
    yearly_savings_percentage: number;
    coming_soon?: boolean;
}

interface CashierSubscription {
    id: number;
    type: string;
    type_display: string | null;
    stripe_status: string;
    stripe_price: string;
    quantity: number;
    trial_ends_at: string | null;
    ends_at: string | null;
    created_at: string;
    on_trial: boolean;
    cancelled: boolean;
    on_grace_period: boolean;
    active: boolean;
    product_name: string | null;
    is_yearly: boolean;
}

interface Props {
    products: BillingProduct[];
    currentSubscription: CashierSubscription | null;
    billingPortalUrl: string;
    hasIncompletePayment: boolean;
    incompletePaymentUrl: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscription',
        href: checkout.subscription().url,
    },
];

export default function CashierSubscription({
    products,
    currentSubscription,
    billingPortalUrl,
    hasIncompletePayment,
    incompletePaymentUrl,
}: Props) {
    const [isSubscribing, setIsSubscribing] = useState(false);
    const [billingInterval, setBillingInterval] = useState<
        'monthly' | 'yearly'
    >('yearly');

    const formatSavings = (value: number) => `$${parseFloat(value.toFixed(2))}`;

    const handleSubscribe = (productId: number) => {
        if (isSubscribing) {
            return;
        }

        setIsSubscribing(true);
        router.post(
            checkout.subscription.store(),
            {
                product_id: productId,
                billing_interval: billingInterval,
            },
            {
                onFinish: () => setIsSubscribing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscription Management (Cashier)" />

            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {hasIncompletePayment && incompletePaymentUrl && (
                        <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-900/20">
                            <div className="flex">
                                <div className="shrink-0">
                                    <TriangleIcon className="h-5 w-5 text-yellow-400 dark:text-yellow-500" />
                                </div>
                                <div className="ml-3 flex-1">
                                    <h3 className="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                                        Payment Required
                                    </h3>
                                    <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>
                                            Your subscription requires
                                            additional payment confirmation.
                                        </p>
                                    </div>
                                    <div className="mt-4">
                                        <Button
                                            asChild
                                            variant="default"
                                            className="bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-800"
                                        >
                                            <a href={incompletePaymentUrl}>
                                                Complete Payment
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {currentSubscription && (
                        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-950">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Current Subscription
                                </h2>
                            </div>
                            <div className="px-6 py-6">
                                <div className="mb-6 flex items-start justify-between">
                                    <div>
                                        <h3 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                            {currentSubscription.product_name ||
                                                currentSubscription.type_display ||
                                                'Subscription'}
                                        </h3>
                                        {currentSubscription.product_name && (
                                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {currentSubscription.is_yearly
                                                    ? 'Yearly Plan'
                                                    : 'Monthly Plan'}
                                            </p>
                                        )}
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-500">
                                            Status:{' '}
                                            <span className="font-medium text-gray-900 dark:text-gray-100">
                                                {currentSubscription.on_trial
                                                    ? 'Trial'
                                                    : currentSubscription.cancelled
                                                      ? 'Cancelled'
                                                      : currentSubscription.active
                                                        ? 'Active'
                                                        : currentSubscription.stripe_status}
                                            </span>
                                        </p>
                                    </div>
                                    <span
                                        className={clsx(
                                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                                            currentSubscription.on_trial
                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                                : currentSubscription.cancelled
                                                  ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                  : currentSubscription.active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                        )}
                                    >
                                        {currentSubscription.on_trial
                                            ? 'Trial'
                                            : currentSubscription.cancelled
                                              ? 'Cancelled'
                                              : currentSubscription.active
                                                ? 'Active'
                                                : currentSubscription.stripe_status}
                                    </span>
                                </div>

                                {(currentSubscription.trial_ends_at ||
                                    currentSubscription.ends_at) && (
                                    <div className="grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                        {currentSubscription.trial_ends_at && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                                    Trial Ends
                                                </p>
                                                <p className="mt-1 font-semibold text-gray-900 dark:text-gray-100">
                                                    {new Date(
                                                        currentSubscription.trial_ends_at,
                                                    ).toLocaleDateString()}
                                                </p>
                                            </div>
                                        )}
                                        {currentSubscription.ends_at && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                                    Ends At
                                                </p>
                                                <p className="mt-1 font-semibold text-gray-900 dark:text-gray-100">
                                                    {new Date(
                                                        currentSubscription.ends_at,
                                                    ).toLocaleDateString()}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Available Plans */}
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-950">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {currentSubscription
                                        ? 'Change Plan'
                                        : 'Choose Your Plan'}
                                </h2>

                                {/* Billing Interval Toggle */}
                                <div className="flex items-center space-x-1 rounded-lg bg-gray-200 p-1 dark:bg-gray-800">
                                    <button
                                        onClick={() =>
                                            setBillingInterval('monthly')
                                        }
                                        className={clsx(
                                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                            billingInterval === 'monthly'
                                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100'
                                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
                                        )}
                                    >
                                        Monthly
                                    </button>
                                    <button
                                        onClick={() =>
                                            setBillingInterval('yearly')
                                        }
                                        className={clsx(
                                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                            billingInterval === 'yearly'
                                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100'
                                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
                                        )}
                                    >
                                        Yearly
                                        <span className="ml-1 text-xs font-semibold text-green-600 dark:text-green-400">
                                            Save{' '}
                                            {products[0]
                                                ?.yearly_savings_percentage ||
                                                17}
                                            %
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div className="px-6 py-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                {products.map((product) => (
                                    <div
                                        key={product.id}
                                        className={clsx(
                                            'relative flex flex-col rounded-lg border p-6 transition-shadow hover:shadow-lg',
                                            product.popular
                                                ? 'border-blue-500 shadow-lg ring-2 ring-blue-500 dark:border-blue-400 dark:ring-blue-400'
                                                : 'border-gray-200 dark:border-gray-700',
                                        )}
                                    >
                                        {product.popular && (
                                            <div className="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                                                <span className="rounded-full bg-blue-500 px-3 py-1 text-xs font-semibold text-white dark:bg-blue-600">
                                                    Most Popular
                                                </span>
                                            </div>
                                        )}

                                        <div className="flex flex-1 flex-col text-center">
                                            <h3 className="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                                {product.name}
                                            </h3>
                                            <div className="mb-4">
                                                {product.coming_soon ? (
                                                    <div>
                                                        <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                            Custom Pricing
                                                        </div>
                                                        <div className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                            Contact us for
                                                            details
                                                        </div>
                                                    </div>
                                                ) : billingInterval ===
                                                  'monthly' ? (
                                                    <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                        {
                                                            product.formatted_price
                                                        }
                                                        <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                            /month
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                            {
                                                                product.formatted_yearly_price
                                                            }
                                                            <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                                /year
                                                            </span>
                                                        </div>
                                                        <div className="text-sm font-medium text-green-600 dark:text-green-400">
                                                            Save{' '}
                                                            {formatSavings(
                                                                product.yearly_savings,
                                                            )}{' '}
                                                            (
                                                            {
                                                                product.yearly_savings_percentage
                                                            }
                                                            %) annually
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="mb-4 flex-1 text-left text-sm">
                                                <p className="mb-3 font-medium text-gray-600 dark:text-gray-400">
                                                    {product.description}
                                                </p>
                                                <ul className="space-y-2 text-gray-600 dark:text-gray-400">
                                                    {product.features.map(
                                                        (feature, index) => (
                                                            <li
                                                                key={index}
                                                                className="flex items-start"
                                                            >
                                                                <span className="mt-0.5 mr-2 shrink-0 text-green-500 dark:text-green-400">
                                                                    ✓
                                                                </span>
                                                                {feature}
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            </div>

                                            <div className="mt-auto">
                                                {product.coming_soon ? (
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        className="w-full"
                                                    >
                                                        <a href={support().url}>
                                                            Contact Sales
                                                        </a>
                                                    </Button>
                                                ) : (
                                                    <>
                                                        <Button
                                                            onClick={() =>
                                                                handleSubscribe(
                                                                    product.id,
                                                                )
                                                            }
                                                            disabled={
                                                                isSubscribing ||
                                                                (billingInterval ===
                                                                'monthly'
                                                                    ? !product.stripe_price_id
                                                                    : !product.yearly_stripe_price_id)
                                                            }
                                                            className="w-full"
                                                            variant={
                                                                product.popular
                                                                    ? 'default'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {isSubscribing
                                                                ? 'Processing...'
                                                                : 'Choose Plan'}
                                                        </Button>

                                                        {((billingInterval ===
                                                            'monthly' &&
                                                            !product.stripe_price_id) ||
                                                            (billingInterval ===
                                                                'yearly' &&
                                                                !product.yearly_stripe_price_id)) && (
                                                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                Stripe Price ID
                                                                required
                                                            </p>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Subscription Management Buttons */}
                    {currentSubscription && (
                        <div className="flex flex-col gap-3">
                            <Button asChild variant="default">
                                <a
                                    href={billingPortalUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <CreditCardIcon className="mr-2 h-4 w-4" />
                                    Manage Subscription
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={billing.index().url}>
                                    <ReceiptIcon className="mr-2 h-4 w-4" />
                                    View Billing History
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
