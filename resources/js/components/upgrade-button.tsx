import checkout from '@/routes/checkout';
import { Link } from '@inertiajs/react';
import { CrownIcon } from 'lucide-react';

export function UpgradeButton() {
    return (
        <Link
            href={checkout.subscription().url}
            className="flex items-center gap-2 rounded-lg border border-purple-300 bg-purple-50 p-3 transition-all hover:bg-purple-100 dark:border-purple-700 dark:bg-purple-950/50 dark:hover:bg-purple-900/50"
        >
            <CrownIcon className="h-4 w-4 text-purple-600 dark:text-purple-400" />
            <span className="font-semibold text-purple-900 dark:text-purple-100">
                Upgrade Now
            </span>
        </Link>
    );
}
