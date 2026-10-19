import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { Link } from '@inertiajs/react';
import { ChevronRight, ScanLine } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export function SnapToTrackLauncher() {
    const { t } = useTranslation('common');

    return (
        <Link
            href={ShowSnapToTrackController().url}
            className="group flex w-full items-center gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-50/60 px-4 py-3 transition-colors hover:border-emerald-500/40 hover:bg-emerald-50 dark:border-emerald-400/20 dark:bg-emerald-900/15 dark:hover:border-emerald-400/40 dark:hover:bg-emerald-900/25"
        >
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-emerald-500 to-teal-600 text-white">
                <ScanLine className="size-4" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-medium text-foreground">
                    {t('snap_to_track.launcher.title')}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {t('snap_to_track.launcher.subtitle')}
                </span>
            </span>
            <ChevronRight className="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
        </Link>
    );
}
