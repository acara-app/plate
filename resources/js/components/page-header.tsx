import AppLogoIcon from '@/components/app-logo-icon';
import useSharedProps from '@/hooks/use-shared-props';

export default function PageHeader() {
    const { currentUser } = useSharedProps();

    return (
        <header className="sticky top-0 z-50 flex w-full items-center justify-between border-b border-slate-100 bg-white/80 px-4 py-4 backdrop-blur-md sm:px-6 lg:px-8">
            <a
                href="/"
                className="flex items-center gap-2 text-xl font-bold text-slate-900"
            >
                <AppLogoIcon className="size-7" />
                <span>Acara</span>
            </a>

            <div className="flex items-center gap-4">
                {currentUser ? (
                    <a
                        href="/dashboard"
                        className="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition-all hover:bg-slate-800"
                    >
                        Dashboard
                    </a>
                ) : (
                    <>
                        <a
                            href="/login"
                            className="text-sm font-medium text-slate-600 transition hover:text-slate-900"
                        >
                            Log in
                        </a>
                        <a
                            href="/register"
                            className="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition-all hover:bg-slate-800"
                        >
                            Get Started
                        </a>
                    </>
                )}
            </div>
        </header>
    );
}
