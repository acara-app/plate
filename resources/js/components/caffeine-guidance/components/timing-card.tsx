import { cn } from '@/lib/utils';

const MORNING_START = 6;
const HOURS_IN_DAY = 24;

const TICK_HOURS = [6, 9, 12, 15, 18, 21, 0, 3];

function tickPosition(hour24: number): number {
    return (
        (((hour24 - MORNING_START + HOURS_IN_DAY) % HOURS_IN_DAY) /
            HOURS_IN_DAY) *
        100
    );
}

function tickLabel(hour24: number): string {
    if (hour24 === 0) {
        return '12a';
    }
    if (hour24 === 12) {
        return '12p';
    }
    return hour24 > 12 ? `${hour24 - 12}p` : `${hour24}a`;
}

function clampHour(hour: number): number {
    return ((Math.floor(hour) % HOURS_IN_DAY) + HOURS_IN_DAY) % HOURS_IN_DAY;
}

export function TimingCard({
    props,
}: {
    props: {
        title: string;
        body: string;
        cutoff_label: string;
        bedtime_label: string;
        cutoff_24h: number;
        bedtime_24h: number;
    };
}) {
    const cutoffHour = clampHour(props.cutoff_24h);
    const bedtimeHour = clampHour(props.bedtime_24h);
    const cutoffPct = tickPosition(cutoffHour);
    const bedPct = tickPosition(bedtimeHour);

    return (
        <section className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] p-6">
            <h3 className="text-2xl leading-tight font-bold tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                {props.title}
            </h3>
            <p className="mt-3 max-w-2xl text-sm leading-relaxed text-[#3D3833]">
                {props.body}
            </p>

            <div
                className="relative mt-12 h-32.5"
                role="img"
                aria-label={`Cutoff at ${props.cutoff_label}, bedtime at ${props.bedtime_label}`}
            >
                {/* main horizontal line */}
                <div className="absolute top-15 right-0 left-0 h-px bg-[#1A1814]" />

                {/* hour ticks below the line */}
                {TICK_HOURS.map((hour) => {
                    const pct = tickPosition(hour);
                    return (
                        <div
                            key={hour}
                            className="absolute top-15.25 -translate-x-1/2"
                            style={{ left: `${pct}%` }}
                        >
                            <div className="mx-auto h-2 w-px bg-[#D9CFBC]" />
                            <div className="mt-1 font-mono text-[10px] tracking-widest whitespace-nowrap text-[#6E665C]">
                                {tickLabel(hour)}
                            </div>
                        </div>
                    );
                })}

                {/* safe-zone bar from start to cutoff */}
                <div
                    className="absolute top-14.5 left-0 h-1 bg-[#6B3F1D]"
                    style={{ width: `${cutoffPct}%` }}
                />

                {/* cutoff marker (above the line) */}
                <div
                    className="absolute top-0 -translate-x-1/2 text-center whitespace-nowrap"
                    style={{ left: `${cutoffPct}%` }}
                >
                    <div className="font-mono text-[10px] tracking-[0.18em] text-[#C4623A] uppercase">
                        cutoff
                    </div>
                    <div className="mt-1 text-base leading-tight font-bold tracking-[-0.01em] text-[#1A1814]">
                        {props.cutoff_label}
                    </div>
                    <div className="mx-auto mt-1 h-4 w-px bg-[#C4623A]" />
                </div>

                {/* bedtime marker (above the line) */}
                <div
                    className="absolute top-0 -translate-x-1/2 text-center whitespace-nowrap"
                    style={{ left: `${bedPct}%` }}
                >
                    <div className="font-mono text-[10px] tracking-[0.18em] text-[#6E665C] uppercase">
                        bed
                    </div>
                    <div className="mt-1 text-base leading-tight font-bold tracking-[-0.01em] text-[#1A1814]">
                        {props.bedtime_label}
                    </div>
                    <div className={cn('mx-auto mt-1 h-4 w-px bg-[#1A1814]')} />
                </div>
            </div>
        </section>
    );
}
