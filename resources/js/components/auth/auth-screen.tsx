import AppLogoIcon from '@/components/app-logo-icon';
import { currentDayPart, DAYLIGHT } from '@/components/chat/day-part';
import { DISPLAY_FONT } from '@/components/dashboard/intake-plate';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { Head } from '@inertiajs/react';
import { ArrowRight, Check, Eye, EyeOff, LoaderCircle } from 'lucide-react';
import {
    type ComponentProps,
    type PropsWithChildren,
    type ReactNode,
    type Ref,
    useState,
} from 'react';

export const AUTH_PRESSABLE =
    'cursor-pointer touch-manipulation select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent focus-visible:outline-none dark:focus-visible:ring-emerald-400';

export const AUTH_FIELD =
    'h-16 w-full rounded-[1.25rem] bg-foreground/[0.055] px-5 text-[1.125rem] font-medium text-foreground caret-emerald-600 outline-none transition-shadow duration-150 ease-out placeholder:font-normal placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-emerald-600 aria-invalid:ring-2 aria-invalid:ring-rose-500 dark:bg-white/[0.08] dark:focus-visible:ring-emerald-400';

export const AUTH_HEADLINE = cn(
    'text-[2.125rem] leading-[1.06] font-extrabold tracking-[-0.035em] text-balance text-foreground',
    DISPLAY_FONT,
);

export const AUTH_LEAD =
    'mt-3 text-[1.0625rem] leading-relaxed text-pretty text-muted-foreground';

export const AUTH_TEXT_LINK = cn(
    'rounded-md font-semibold text-foreground underline decoration-foreground/30 underline-offset-4',
    AUTH_PRESSABLE,
);

export const AUTH_ICON_BUTTON = cn(
    'flex size-11 items-center justify-center rounded-full bg-foreground/[0.055] text-foreground transition-[scale,background-color] duration-150 ease-out active:scale-90 dark:bg-white/[0.08]',
    AUTH_PRESSABLE,
);

export const AUTH_SOFT_BUTTON =
    '[&_button]:h-14 [&_button]:rounded-full [&_button]:border-0 [&_button]:bg-foreground/[0.055] [&_button]:text-base [&_button]:font-semibold [&_button]:shadow-none dark:[&_button]:bg-white/[0.08]';

export const AUTH_ENTER =
    'duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] fill-mode-both motion-safe:animate-in motion-safe:fade-in';

export function AuthScreen({
    title,
    children,
}: PropsWithChildren<{ title: string }>) {
    const [dayPart] = useState(currentDayPart);

    return (
        <div className="relative isolate flex min-h-dvh flex-col overflow-x-clip bg-background md:justify-center">
            <Head title={title}>
                <link
                    rel="stylesheet"
                    href="https://fonts.bunny.net/css?family=sn-pro:600,700,800&display=swap"
                />
            </Head>
            <div
                aria-hidden="true"
                className={cn(
                    'pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-radial-[at_22%_-10%] to-transparent to-70%',
                    DAYLIGHT[dayPart],
                )}
            />
            <div className="mx-auto flex w-full max-w-[27rem] flex-1 flex-col px-6 pt-[max(1.25rem,env(safe-area-inset-top))] pb-[max(1.5rem,env(safe-area-inset-bottom))] md:min-h-[42rem] md:flex-none md:py-10">
                {children}
            </div>
        </div>
    );
}

export function AuthBrand() {
    return (
        <a
            href={home().url}
            aria-label="Acara"
            className={cn(
                'flex size-11 items-center rounded-full',
                AUTH_PRESSABLE,
            )}
        >
            <AppLogoIcon className="size-9" />
        </a>
    );
}

export function AuthDivider({ children }: { children: ReactNode }) {
    return (
        <p className="py-5 text-center text-sm text-muted-foreground">
            {children}
        </p>
    );
}

export function AuthSubmitButton({
    children,
    processing = false,
    ready = true,
    className,
    ...props
}: ComponentProps<'button'> & { processing?: boolean; ready?: boolean }) {
    return (
        <button
            type="submit"
            disabled={processing}
            className={cn(
                'flex h-14 w-full items-center justify-center gap-2.5 rounded-full bg-foreground text-[1.0625rem] font-bold text-background shadow-[0_12px_24px_-12px_rgb(0_0_0/0.5)] transition-[scale,opacity] duration-150 ease-out active:scale-[0.97] disabled:opacity-60',
                DISPLAY_FONT,
                AUTH_PRESSABLE,
                !ready && 'opacity-45',
                className,
            )}
            {...props}
        >
            {children}
            {processing ? (
                <LoaderCircle
                    className="size-5 animate-spin"
                    aria-hidden="true"
                />
            ) : (
                <ArrowRight
                    className="size-5"
                    strokeWidth={2.5}
                    aria-hidden="true"
                />
            )}
        </button>
    );
}

export function AuthCheck({
    checked,
    className,
}: {
    checked: boolean;
    className?: string;
}) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex size-6 shrink-0 items-center justify-center rounded-full transition-[background-color,box-shadow] duration-150 ease-out peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-600 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-emerald-400',
                checked
                    ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                    : 'bg-white inset-ring-[1.5px] inset-ring-black/20 dark:bg-white/5 dark:inset-ring-white/25',
                className,
            )}
        >
            <Check
                strokeWidth={3.25}
                className={cn(
                    'size-[62%] transition-[scale,opacity] duration-150 ease-out',
                    checked ? 'scale-100 opacity-100' : 'scale-75 opacity-0',
                )}
            />
        </span>
    );
}

export function AuthPasswordInput({
    className,
    ref,
    ...props
}: Omit<ComponentProps<'input'>, 'type'> & { ref?: Ref<HTMLInputElement> }) {
    const [isShown, setIsShown] = useState(false);
    const Icon = isShown ? EyeOff : Eye;

    return (
        <div className="relative">
            <input
                ref={ref}
                type={isShown ? 'text' : 'password'}
                className={cn(AUTH_FIELD, 'pr-14', className)}
                {...props}
            />
            <button
                type="button"
                onClick={() => setIsShown((value) => !value)}
                aria-label={isShown ? 'Hide password' : 'Show password'}
                aria-pressed={isShown}
                className={cn(
                    'absolute inset-y-0 right-2.5 my-auto flex size-11 items-center justify-center rounded-full text-muted-foreground transition-[scale,color] duration-150 ease-out hover:text-foreground active:scale-90',
                    AUTH_PRESSABLE,
                )}
            >
                <Icon className="size-5" aria-hidden="true" />
            </button>
        </div>
    );
}

export function AuthFieldError({
    message,
    className,
}: {
    message?: string | false | null;
    className?: string;
}) {
    if (!message) {
        return null;
    }

    return (
        <p
            role="alert"
            className={cn(
                'text-sm font-medium text-rose-600 duration-200 ease-out motion-safe:animate-in motion-safe:fade-in dark:text-rose-400',
                className,
            )}
        >
            {message}
        </p>
    );
}
