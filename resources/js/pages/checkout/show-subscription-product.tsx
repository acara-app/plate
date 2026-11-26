import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { PaymentConfirmation } from '@/components/billing/payment-confirmation';
import InputError from '@/components/input-error';
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
    stripe_price_id: string | null;
    popular: boolean;
    formatted_price: string;
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
    planActive: boolean;
    products: BillingProduct[];
    currentSubscription: CashierSubscription | null;
    billingPortalUrl: string | null;
    hasIncompletePayment: boolean;
    incompletePaymentUrl: string | null;
}

const press =
    'touch-manipulation select-none [-webkit-tap-highlight-color:transparent] [-webkit-touch-callout:none] transition-[color,box-shadow,scale] duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100';
const touchButton = `h-auto min-h-11 ${press}`;
const planButton = `${touchButton} w-full whitespace-normal`;
const navLink = `inline-flex min-h-11 items-center ${press}`;

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
    planActive,
    currentSubscription,
    billingPortalUrl,
    hasIncompletePayment,
    incompletePaymentUrl,
}: Props) {
    const [isSubscribing, setIsSubscribing] = useState(false);
    const [checkoutError, setCheckoutError] = useState<{
        productId: number;
        message: string;
    } | null>(null);
    const { t } = useTranslation('common');

    const Layout = isGuest ? GuestPricingLayout : AppLayout;
    const selectedPlan =
        isGuest || paymentPending || planActive || currentSubscription
            ? undefined
            : products.find((product) => product.id === selectedProductId);

    useEffect(() => {
        if (continueToMeal && meal?.save_url) {
            router.visit(meal.save_url, { replace: true });
        }
    }, [continueToMeal, meal?.save_url]);

    function handleSubscribe(productId: number) {
        if (isSubscribing) {
            return;
        }

        setCheckoutError(null);
        window.umami?.track('pricing_checkout_click', {
            audience: isGuest ? 'guest' : 'member',
            product_id: productId,
            interval: 'monthly',
        });
        setIsSubscribing(true);
        router.post(
            checkout.subscription.store(),
            {
                product_id: productId,
            },
            {
                preserveScroll: 'errors',
                onError(errors) {
                    setCheckoutError({
                        productId,
                        message: errors.checkout ?? Object.values(errors)[0],
                    });
                },
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
                                {selectedPlan.formatted_price}
                                {t('checkout_subscription.per_month')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('photo_plans.checkout.monthly_terms', {
                                    price: selectedPlan.formatted_price,
                                })}
                            </p>
                            <Button
                                onClick={() => handleSubscribe(selectedPlan.id)}
                                disabled={isSubscribing}
                                className={`${planButton} sm:w-auto`}
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
                                        {t('photo_plans.checkout.save_meal')}
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
                                            className={`${touchButton} bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-700 dark:hover:bg-yellow-800`}
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

                    {checkoutError &&
                        !products.some(
                            (product) => product.id === checkoutError.productId,
                        ) && (
                            <InputError
                                role="alert"
                                message={checkoutError.message}
                            />
                        )}
                    <div
                        className={clsx(
                            'grid grid-cols-1 gap-6',
                            products.length === 2 && 'md:grid-cols-2',
                            products.length > 2 && 'md:grid-cols-3',
                        )}
                    >
                        {products.map((product) => (
                            <div
                                key={product.id}
                                className={clsx(
                                    'relative flex min-w-0 flex-col rounded-lg border bg-card p-4 wrap-break-word transition-shadow hover:shadow-lg sm:p-6',
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
                                    <h2 className="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        {product.name}
                                        {selectedProductId === product.id && (
                                            <span className="block text-sm text-muted-foreground">
                                                {t(
                                                    'photo_plans.checkout.selected',
                                                )}
                                            </span>
                                        )}
                                    </h2>
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
                                        ) : (
                                            <div className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                                {product.formatted_price}
                                                <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                    {t(
                                                        'checkout_subscription.per_month',
                                                    )}
                                                </span>
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
                                                className={planButton}
                                            >
                                                <a href={support().url}>
                                                    {t(
                                                        'checkout_subscription.contact_sales',
                                                    )}
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
                                                        !product.stripe_price_id
                                                    }
                                                    className={planButton}
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
                                                                'photo_plans.checkout.pay',
                                                            )
                                                          : t(
                                                                'photo_plans.checkout.choose',
                                                                {
                                                                    name: product.name,
                                                                },
                                                            )}
                                                </Button>
                                                {checkoutError?.productId ===
                                                    product.id && (
                                                    <InputError
                                                        role="alert"
                                                        className="mt-2"
                                                        message={
                                                            checkoutError.message
                                                        }
                                                    />
                                                )}

                                                <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                                                    {t(
                                                        'photo_plans.checkout.monthly_terms',
                                                        {
                                                            price: product.formatted_price,
                                                        },
                                                    )}
                                                </p>
                                                {!product.stripe_price_id && (
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

                    {/* Subscription Management Buttons */}
                    {currentSubscription && (
                        <div className="flex flex-col gap-3">
                            <Button
                                asChild
                                variant="default"
                                className={touchButton}
                            >
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
                            <Button
                                asChild
                                variant="outline"
                                className={touchButton}
                            >
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
        <main className="min-h-svh bg-background text-foreground">
            <nav className="mx-auto flex max-w-7xl justify-between px-4 py-1.5 sm:px-6 lg:px-8">
                <a href={snapToTrack().url} className={navLink}>
                    Snap to Track
                </a>
                <Link href={login().url} className={navLink}>
                    {t('photo_plans.checkout.login')}
                </Link>
            </nav>
            {children}
        </main>
    );
}
