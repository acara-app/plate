import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
    snap?: boolean;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
    snap = false,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div
            className={`flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 ${snap ? 'bg-[#F2EBDD] text-[#1A1814] [--color-background:#F2EBDD] [--color-border:#847668] [--color-foreground:#1A1814] [--color-input:#847668] [--color-muted-foreground:#5C544A] [--color-primary-foreground:#FFFFFF] [--color-primary:#A04A28] [--color-ring:#A04A28]' : 'bg-background'}`}
        >
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <a
                            href={home().url}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-9 fill-current text-(--foreground) dark:text-white" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </a>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            {description && (
                                <p className="text-center text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
