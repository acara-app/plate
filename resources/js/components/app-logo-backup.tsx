import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="relative flex aspect-square size-8 items-center justify-center text-sidebar-primary-foreground">
                <span className="absolute inset-0 rounded-xl bg-[linear-gradient(140deg,#34d399_0%,#059669_45%,#065f46_100%)] ring-1 ring-white/15" />
                <span className="absolute inset-px rounded-[0.95rem] bg-[radial-gradient(circle_at_25%_20%,rgba(255,255,255,0.85),rgba(255,255,255,0)_55%),radial-gradient(circle_at_80%_80%,rgba(16,133,101,0.45),rgba(255,255,255,0)_70%)] opacity-90 mix-blend-screen" />
                <span className="absolute inset-px rounded-[0.95rem] bg-[conic-gradient(from_120deg_at_50%_40%,rgba(255,255,255,0.75)_0deg,rgba(255,255,255,0.05)_120deg,rgba(2,44,34,0.45)_210deg,rgba(255,255,255,0.65)_360deg)] opacity-75 mix-blend-overlay" />
                <span className="absolute inset-[3px] rounded-[0.8rem] bg-[linear-gradient(165deg,rgba(255,255,255,0.2)_0%,rgba(255,255,255,0.05)_45%,rgba(12,74,56,0.6)_100%)] opacity-70" />
                <span className="absolute top-[18%] left-[12%] h-2 w-4 rounded-full bg-white/75 blur-[2px]" />
                <span className="absolute right-[15%] bottom-[18%] h-2 w-2 rounded-full bg-emerald-300/90 blur-[1px]" />
                <div className="relative z-10 flex size-full items-center justify-center rounded-xl bg-emerald-500/5 backdrop-blur-[2px]">
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                </div>
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Nutrition Agent
                </span>
            </div>
        </>
    );
}
