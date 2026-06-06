import AppLogoIcon from './app-logo-icon';

interface AppLogoProps {
    showText?: boolean;
}

export default function AppLogo({ showText = true }: AppLogoProps) {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-800">
                <AppLogoIcon className="size-6 fill-current" />
            </div>
            {showText && (
                <div className="ml-1 grid flex-1 text-left text-sm">
                    <span className="mb-0.5 truncate leading-tight font-semibold">
                        Acara Plate
                    </span>
                </div>
            )}
        </>
    );
}
