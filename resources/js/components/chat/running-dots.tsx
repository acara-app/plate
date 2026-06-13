export function RunningDots() {
    return (
        <span className="flex shrink-0 items-center gap-0.5" aria-hidden="true">
            <span className="size-1.5 rounded-full bg-current [animation-delay:0ms] motion-safe:animate-bounce" />
            <span className="size-1.5 rounded-full bg-current [animation-delay:150ms] motion-safe:animate-bounce" />
            <span className="size-1.5 rounded-full bg-current [animation-delay:300ms] motion-safe:animate-bounce" />
        </span>
    );
}
