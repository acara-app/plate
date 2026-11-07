import {
    PhotoAllowanceCard,
    type PhotoAllowance,
} from '@/components/photo-allowance';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { PaymentConfirmation } from '@/components/billing/payment-confirmation';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { support, login, snapToTrack } from '@/routes';
import billing from '@/routes/billing';
import checkout from '@/routes/checkout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import clsx from 'clsx';
import { CreditCardIcon, ReceiptIcon, TriangleIcon } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

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

interface PendingMeal {
    expired: boolean;
    save_url: string | null;
}

interface Props {
    isGuest: boolean;
    paymentPending: boolean;
    checkoutCancelled: boolean;
    emailVerified: boolean;
    meal: PendingMeal | null;
    continueToMeal: boolean;
    selectedProductId: number | null;
    selectedInterval: 'monthly' | 'yearly';
    photoAllowance?: PhotoAllowance;
    products: BillingProduct[];
    currentSubscription: CashierSubscription | null;
    billingPortalUrl: string | null;
    hasIncompletePayment: boolean;
    incompletePaymentUrl: string | null;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('checkout_subscription.breadcrumb'),
        href: checkout.subscription().url,
    },
];

export default function CashierSubscription({
    products,
    isGuest,
    paymentPending,
    checkoutCancelled,
    emailVerified,
    meal,
    continueToMeal,
    selectedProductId,
    selectedInterval,
    photoAllowance,
    currentSubscription,
    billingPortalUrl,
    hasIncompletePayment,
    incompletePaymentUrl,
}: Props) {
    const [isSubscribing, setIsSubscribing] = useState(false);
    const [billingInterval, setBillingInterval] = useState<
        'monthly' | 'yearly'
    >(selectedInterval);
    const { t } = useTranslation('common');

    const Layout = isGuest ? GuestPricingLayout : AppLayout;
    const planActive = !paymentPending && photoAllowance?.mode === 'premium';
    const selectedPlan =
        isGuest || paymentPending || planActive || currentSubscription
            ? undefined
            : products.find(
                  (product) =>
                      product.id === selectedProductId && product.price > 0,
              );

    useEffect(() => {
        if (continueToMeal && meal?.save_url) {
            router.visit(meal.save_url, { replace: true });
        }
    }, [continueToMeal, meal?.save_url]);

    const formatSavings = (value: number) => `$${parseFloat(value.toFixed(2))}`;

    function handleSubscribe(
        productId: number,
        interval: 'monthly' | 'yearly' = billingInterval,
    ) {
        if (isSubscribing) {
            return;
        }

        if (isGuest) {
            router.visit(
                checkout.start(productId, { query: { interval } }).url,
            );
            return;
        }
        window.umami?.track('snap_to_track_checkout_click', {
            source: 'checkout',
            interval,
        });
        setIsSubscribing(true);
        router.post(
            checkout.subscription.store(),
            {
                product_id: productId,
                billing_interval: interval,
            },
            {
                onFinish() {
                    setIsSubscribing(false);
                },
            },
        );
    }

    return (
        <Layout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('checkout_subscription.title')} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                <div className="space-y-8">
                    {paymentPending && (
                        <div className="space-y-2">
                            <PaymentConfirmation />
                            {meal?.save_url && (
                                <p className="text-sm text-muted-foreground">
                                    {t('photo_plans.checkout.pending_meal')}
                                </p>
                            )}
                        </div>
                    )}
                    {planActive && (
                        <div role="status" className="rounded-lg border p-4">
                            {t('photo_plans.checkout.active')}{' '}
                            {meal?.save_url ? (
                                <Link
                                    href={meal.save_url}
                                    className="underline"
                                >
                                    {t('photo_plans.checkout.review')}
                                </Link>
                            ) : (
                                <>
                                    {meal?.expired &&
                                        t('photo_plans.checkout.expired')}{' '}
                                    <Link
                                        href={ShowSnapToTrackController()}
                                        className="underline"
                                    >
                                        {meal?.expired
                                            ? t(
                                                  'photo_plans.checkout.try_another',
                                              )
                                            : t(
                                                  'photo_plans.checkout.continue_scanning',
                                              )}
                                    </Link>
                                </>
                            )}
                        </div>
                    )}
                    {selectedPlan && (
                        <section
                            aria-labelledby="selected-plan-heading"
                            className="space-y-3 rounded-lg border border-primary/30 p-4 sm:p-6"
                        >
                            {checkoutCancelled && (
                                <p className="text-sm text-muted-foreground">
                                    {t('photo_plans.checkout.cancelled')}
                                </p>
                            )}
                            <h2
                                id="selected-plan-heading"
                                className="text-lg font-semibold"
                            >
                                {t('photo_plans.checkout.continue', {
                                    name: selectedPlan.name,
                                })}
                            </h2>
                            <p className="text-2xl font-bold">
                                {selectedInterval === 'yearly'
                                    ? `${selectedPlan.formatted_yearly_price}${t('checkout_subscription.per_year')}`
                                    : `${selectedPlan.formatted_price}${t('checkout_subscription.per_month')}`}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t(
                                    `photo_plans.checkout.${selectedInterval}_terms`,
                                    {
                                        price:
                                            selectedInterval === 'yearly'
                                                ? selectedPlan.formatted_yearly_price
                                                : selectedPlan.formatted_price,
                                    },
                                )}
                            </p>
                            <Button
                                onClick={() =>
                                    handleSubscribe(
                                        selectedPlan.id,
                                        selectedInterval,
                                    )
                                }
                                disabled={isSubscribing}
                                className="h-auto min-h-11 w-full whitespace-normal sm:w-auto"
                            >
                                {isSubscribing
                                    ? t('checkout_subscription.processing')
                                    : t('photo_plans.checkout.pay')}
                            </Button>
                            {meal?.save_url &&
                                (emailVerified ? (
                                    <Link
                                        href={meal.save_url}
                                        className="block text-sm underline"
                                    >
                                        {t('photo_plans.checkout.back_meal')}
                                    </Link>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        <Link
                                            href={meal.save_url}
                                            className="underline"
                                        >
                                            {t(
                                                'photo_plans.checkout.verify_save',
                                            )}
                                        </Link>
                                    </p>
                                ))}
                        </section>
                    )}
                    {hasIncompletePayment && incompletePaymentUrl && (
                        <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-900/20">
                            <div className="flex">
                                <div className="shrink-0">
                                    <TriangleIcon className="h-5 w-5 text-yellow-400 dark:text-yellow-500" />
                                </div>
                                <div className="ml-3 flex-1">
                                    <h3 className="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                                        {t(
                                            'checkout_subscription.payment_required',
                                        )}
                                    </h3>
                                    <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>
                                            {t(
                                                'checkout_subscription.payment_required_description',
                                            )}
                                        </p>
                                    </div>
                                    <div className="mt-4">
                                        <Button
                                            asChild
                                            variant="default"
                                            className="bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-800"
                                        >
                                            <a href={incompletePaymentUrl}>
                                                {t(
                                                    'checkout_subscription.complete_payment',
                                                )}
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
                                    {t(
                                        'checkout_subscription.current_subscription',
                                    )}
                                </h2>
                            </div>
                            <div className="p-4 sm:p-6">
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
                                                    ? t(
                                                          'checkout_subscription.yearly_plan',
                                                      )
                                                    : t(
                                                          'checkout_subscription.monthly_plan',
                                                      )}
                                            </p>
                                        )}
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-500">
                                            {t('checkout_subscription.status')}:{' '}
                                            <span className="font-medium text-gray-900 dark:text-gray-100">
                                                {currentSubscription.on_trial
                                                    ? t(
                                                          'checkout_subscription.trial',
                                                      )
                                                    : currentSubscription.cancelled
                                                      ? t(
                                                            'checkout_subscription.cancelled',
                                                        )
                                                      : currentSubscription.active
                                                        ? t(
                                                              'checkout_subscription.active',
                                                          )
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
                                            ? t('checkout_subscription.trial')
                                            : currentSubscription.cancelled
                                              ? t(
                                                    'checkout_subscription.cancelled',
                                                )
                                              : currentSubscription.active
                                                ? t(
                                                      'checkout_subscription.active',
                                                  )
                                                : currentSubscription.stripe_status}
                                    </span>
                                </div>

                                {(currentSubscription.trial_ends_at ||
                                    currentSubscription.ends_at) && (
                                    <div className="grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                        {currentSubscription.trial_ends_at && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                                    {t(
                                                        'checkout_subscription.trial_ends',
                                                    )}
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
                                                    {t(
                                                        'checkout_subscription.ends_at',
                                                    )}
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

                    <PhotoAllowanceCard
                        allowance={photoAllowance}
                        showUpgrade={false}
                    />

                    {/* Available Plans */}
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-950">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {currentSubscription
                                        ? t('checkout_subscription.change_plan')
                                        : t(
                                              'checkout_subscription.choose_plan',
                                          )}
                                </h2>

                                {/* Billing Interval Toggle */}
                                {products.some(
                                    (product) => product.yearly_price,
                                ) && (
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
                                            {t('checkout_subscription.monthly')}
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
                                            {t('checkout_subscription.yearly')}
                                            <span className="ml-1 text-xs font-semibold text-green-600 dark:text-green-400">
                                                {t(
                                                    'checkout_subscription.save',
                                                )}{' '}
                                                {products[0]
                                                    ?.yearly_savings_percentage ||
                                                    17}
                                                %
                                            </span>
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="p-4 sm:p-6">
                            <div
                                className={clsx(
                                    'grid grid-cols-1 gap-6',
                                    products.length === 2
                                        ? 'md:grid-cols-2'
                                        : 'md:grid-cols-3',
                                )}
                            >
                                {products.map((product) => (
                                    <div
                                        key={product.id}
                                        className={clsx(
                                            'relative flex min-w-0 flex-col rounded-lg border p-4 wrap-break-word transition-shadow hover:shadow-lg sm:p-6',
                                            product.popular
                                                ? 'border-blue-500 shadow-lg ring-2 ring-blue-500 dark:border-blue-400 dark:ring-blue-400'
                                                : 'border-gray-200 dark:border-gray-700',
                                        )}
                                    >
                                        {product.popular && (
                                            <div className="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                                                <span className="rounded-full bg-blue-500 px-3 py-1 text-xs font-semibold text-white dark:bg-blue-600">
                                                    {t(
                                                        'checkout_subscription.most_popular',
                                                    )}
                                                </span>
                                            </div>
                                        )}

                                        <div className="flex flex-1 flex-col text-center">
                                            <h3 className="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                                {product.name}
                                                {selectedProductId ===
                                                    product.id && (
                                                    <span className="block text-sm text-muted-foreground">
                                                        {t(
                                                            'photo_plans.checkout.selected',
                                                        )}
                                                    </span>
                                                )}
                                            </h3>
                                            <div className="mb-4">
                                                {product.coming_soon ? (
                                                    <div>
                                                        <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                            {t(
                                                                'checkout_subscription.custom_pricing',
                                                            )}
                                                        </div>
                                                        <div className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                            {t(
                                                                'checkout_subscription.contact_us',
                                                            )}
                                                        </div>
                                                    </div>
                                                ) : product.price === 0 ? (
                                                    <div className="text-3xl font-bold">
                                                        {t(
                                                            'photo_plans.free.price',
                                                        )}
                                                    </div>
                                                ) : billingInterval ===
                                                  'monthly' ? (
                                                    <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                        {
                                                            product.formatted_price
                                                        }
                                                        <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                            {t(
                                                                'checkout_subscription.per_month',
                                                            )}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                            {
                                                                product.formatted_yearly_price
                                                            }
                                                            <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                                {t(
                                                                    'checkout_subscription.per_year',
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div className="text-sm font-medium text-green-600 dark:text-green-400">
                                                            {t(
                                                                'checkout_subscription.save_amount',
                                                                {
                                                                    amount: formatSavings(
                                                                        product.yearly_savings,
                                                                    ),
                                                                    percentage:
                                                                        product.yearly_savings_percentage,
                                                                },
                                                            )}
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
                                                        className="h-auto min-h-11 w-full whitespace-normal"
                                                    >
                                                        <a href={support().url}>
                                                            {t(
                                                                'checkout_subscription.contact_sales',
                                                            )}
                                                        </a>
                                                    </Button>
                                                ) : product.price === 0 &&
                                                  photoAllowance?.enabled ? (
                                                    photoAllowance.mode ===
                                                        'trial' &&
                                                    !photoAllowance.exhausted ? (
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            className="h-auto min-h-11 w-full whitespace-normal"
                                                        >
                                                            <a
                                                                href={
                                                                    snapToTrack()
                                                                        .url
                                                                }
                                                            >
                                                                {t(
                                                                    'photo_plans.free.try',
                                                                )}
                                                            </a>
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            disabled
                                                            variant="outline"
                                                            className="h-auto min-h-11 w-full whitespace-normal"
                                                        >
                                                            {photoAllowance.mode ===
                                                            'premium'
                                                                ? t(
                                                                      'photo_plans.checkout.premium_active',
                                                                  )
                                                                : t(
                                                                      'photo_plans.free.used',
                                                                  )}
                                                        </Button>
                                                    )
                                                ) : product.price === 0 ? (
                                                    <Button
                                                        disabled
                                                        variant="outline"
                                                        className="h-auto min-h-11 w-full whitespace-normal"
                                                    >
                                                        {currentSubscription
                                                            ? t(
                                                                  'checkout_subscription.free_plan_label',
                                                              )
                                                            : t(
                                                                  'checkout_subscription.current_plan_label',
                                                              )}
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
                                                            className="h-auto min-h-11 w-full whitespace-normal"
                                                            variant={
                                                                product.popular
                                                                    ? 'default'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {isSubscribing
                                                                ? t(
                                                                      'checkout_subscription.processing',
                                                                  )
                                                                : isGuest
                                                                  ? t(
                                                                        'checkout_subscription.photo_plan.guest_cta',
                                                                    )
                                                                  : t(
                                                                        'photo_plans.checkout.choose',
                                                                        {
                                                                            name: product.name,
                                                                        },
                                                                    )}
                                                        </Button>

                                                        <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                                                            {t(
                                                                `photo_plans.checkout.${billingInterval}_terms`,
                                                                {
                                                                    price:
                                                                        billingInterval ===
                                                                        'yearly'
                                                                            ? product.formatted_yearly_price
                                                                            : product.formatted_price,
                                                                },
                                                            )}
                                                        </p>
                                                        {((billingInterval ===
                                                            'monthly' &&
                                                            !product.stripe_price_id) ||
                                                            (billingInterval ===
                                                                'yearly' &&
                                                                !product.yearly_stripe_price_id)) && (
                                                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                {t(
                                                                    'checkout_subscription.stripe_price_required',
                                                                )}
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
                                    href={
                                        billingPortalUrl ?? billing.index().url
                                    }
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <CreditCardIcon className="h-4 w-4" />
                                    {t(
                                        'checkout_subscription.manage_subscription',
                                    )}
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={billing.index().url}>
                                    <ReceiptIcon className="h-4 w-4" />
                                    {t(
                                        'checkout_subscription.view_billing_history',
                                    )}
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}

function GuestPricingLayout({
    children,
}: {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const { t } = useTranslation('common');

    return (
        <main className="min-h-screen bg-background text-foreground">
            <nav className="mx-auto flex max-w-7xl justify-between px-6 py-4">
                <a href={snapToTrack().url}>Snap to Track</a>
                <Link href={login().url}>
                    {t('photo_plans.checkout.login')}
                </Link>
            </nav>
            {children}
        </main>
    );
}
