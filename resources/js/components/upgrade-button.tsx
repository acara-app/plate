import { cn } from '@/lib/utils';
import checkout from '@/routes/checkout';
import { Link } from '@inertiajs/react';
import { CrownIcon } from 'lucide-react';
import type { ComponentPropsWithRef } from 'react';

export function UpgradeButton({
    className,
    ...props
}: ComponentPropsWithRef<typeof Link>) {
    return (
        <Link
            href={checkout.subscription().url}
            className={cn('flex items-center gap-2 transition-all', className)}
            {...props}
        >
            <CrownIcon className="h-4 w-4 shrink-0 text-purple-600 dark:text-purple-400" />
            <span className="font-semibold text-purple-900 dark:text-purple-100">
                Upgrade
            </span>
        </Link>
    );
}
