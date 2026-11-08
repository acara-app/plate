import UserController from '@/actions/App/Http/Controllers/UserController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import billing from '@/routes/billing';
import { Form, Link, usePage } from '@inertiajs/react';
import { useRef } from 'react';

export default function DeleteUser({ hasPassword }: { hasPassword: boolean }) {
    const confirmationInput = useRef<HTMLInputElement>(null);
    const { auth } = usePage().props;

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="Delete account"
                description="Delete your account and all of its resources"
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Warning</p>
                    <p className="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>

                {auth.subscribed ? (
                    <div className="space-y-2">
                        <p className="text-sm text-red-600 dark:text-red-100">
                            You have an active subscription. Please cancel your
                            subscription before deleting your account.
                        </p>
                        <Button variant="outline" asChild>
                            <Link href={billing.index().url}>
                                Manage subscription
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="destructive"
                                data-test="delete-user-button"
                            >
                                Delete account
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                Are you sure you want to delete your account?
                            </DialogTitle>
                            <DialogDescription>
                                Once your account is deleted, all of its
                                resources and data will also be permanently
                                deleted.{' '}
                                {hasPassword
                                    ? 'Please enter your password to confirm you would like to permanently delete your account.'
                                    : `Please type your email address (${auth.user.email}) to confirm you would like to permanently delete your account.`}
                            </DialogDescription>

                            <Form
                                {...UserController.destroy.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                onError={() =>
                                    confirmationInput.current?.focus()
                                }
                                resetOnSuccess
                                className="space-y-6"
                            >
                                {({
                                    resetAndClearErrors,
                                    processing,
                                    errors,
                                }) => (
                                    <>
                                        {hasPassword ? (
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor="password"
                                                    className="sr-only"
                                                >
                                                    Password
                                                </Label>

                                                <Input
                                                    id="password"
                                                    type="password"
                                                    name="password"
                                                    ref={confirmationInput}
                                                    placeholder="Password"
                                                    autoComplete="current-password"
                                                />

                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>
                                        ) : (
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor="email"
                                                    className="sr-only"
                                                >
                                                    Email address
                                                </Label>

                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    ref={confirmationInput}
                                                    placeholder={
                                                        auth.user.email
                                                    }
                                                    autoComplete="off"
                                                />

                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>
                                        )}

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() =>
                                                        resetAndClearErrors()
                                                    }
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                asChild
                                            >
                                                <button
                                                    type="submit"
                                                    data-test="confirm-delete-user-button"
                                                >
                                                    Delete account
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </div>
    );
}
