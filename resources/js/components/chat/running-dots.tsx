export function RunningDots() {
    return (
        <span className="flex shrink-0 items-center gap-0.5" aria-hidden="true">
            <span className="size-1 rounded-full bg-emerald-500 motion-safe:animate-pulse" />
            <span className="size-1 rounded-full bg-emerald-500 [animation-delay:150ms] motion-safe:animate-pulse" />
            <span className="size-1 rounded-full bg-emerald-500 [animation-delay:300ms] motion-safe:animate-pulse" />
        </span>
    );
}
