export function RunningDots() {
    return (
        <span className="flex shrink-0 items-center gap-0.5" aria-hidden="true">
            <span className="size-1.5 rounded-full bg-current motion-safe:animate-bounce [animation-delay:0ms]" />
            <span className="size-1.5 rounded-full bg-current motion-safe:animate-bounce [animation-delay:150ms]" />
            <span className="size-1.5 rounded-full bg-current motion-safe:animate-bounce [animation-delay:300ms]" />
        </span>
    );
}
