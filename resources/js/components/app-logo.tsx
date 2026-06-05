import AppLogoIcon from './app-logo-icon';

interface AppLogoProps {
    showText?: boolean;
}

export default function AppLogo({ showText = true }: AppLogoProps) {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-emerald-500 text-white dark:bg-emerald-500">
                <AppLogoIcon className="size-6 fill-current text-white dark:text-black" />
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
